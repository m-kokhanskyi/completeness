<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see http://treopim.com/eula.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Espo\Modules\Completeness\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Modules\Pim\Services\DashletInterface;
use Treo\Services\AbstractService;

/**
 * Class CompletenessOverviewDashlet
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CompletenessOverviewDashlet extends AbstractService implements DashletInterface
{
    /**
     * Get dashlet data
     *
     * @return array
     * @throws Error
     */
    public function getDashlet(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // get completeness fields
        $completenessFields = $this->getCompletenessFieldInProduct();

        if (count($completenessFields) > 0) {
            // push channel complete
            $result['list'] = $this->getChannelComplete($completenessFields);

            // push total
            $result['list'][] = $this->getCompletenessTotal($completenessFields);

            // prepare total
            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * Get completeness field in product
     *
     * @return array
     * @throws Error
     */
    protected function getCompletenessFieldInProduct(): array
    {
        // get Product fields
        $fields = $this->getMetadata()->get('entityDefs.Product.fields');
        $result = [];

        foreach ($fields as $fieldName => $fieldData) {
            if ($fieldData['isCompleteness'] ?? false) {
                $result[$this->getLanguageKey($fieldName)] = $fieldName;
            }
        }

        return $result;
    }

    /**
     * Get channel complete overview
     *
     * @param array $completenessFields
     *
     * @return array
     */
    protected function getChannelComplete(array $completenessFields): array
    {
        // prepare result
        $result = [];

        // get products channel data
        $data = $this->getProductChannelData();

        // get products completes
        $completes = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->select(array_merge(['id'], $completenessFields))
            ->where(['id' => array_column($data, 'productId')])
            ->find()
            ->toArray();

        // prepare channels
        $channels = [];
        foreach ($data as $row) {
            foreach ($completes as $v) {
                if ($v['id'] == $row['productId']) {
                    unset($v['id']);
                    $row = array_merge($row, $v);
                }
            }
            $channels[$row['channelId']]['channelId'] = $row['channelId'];
            $channels[$row['channelId']]['channelName'] = $row['channelName'];
            $channels[$row['channelId']]['products'][] = $row;
            foreach ($completenessFields as $key => $field) {
                if (!isset($channels[$row['channelId']][$key])) {
                    $channels[$row['channelId']][$key] = 0;
                }
                $channels[$row['channelId']][$key] += $row[$field];
            }
        }

        // prepare result
        foreach ($channels as $row) {
            $item = [
                'id'   => $row['channelId'],
                'name' => $row['channelName'],
            ];
            foreach ($completenessFields as $key => $field) {
                $item[$key] = round(($row[$key] / count($row['products'])), 2);
            }

            // push
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Get completeness total data
     *
     * @param array $completenessFields
     *
     * @return array
     */
    protected function getCompletenessTotal(array $completenessFields): array
    {
        // prepare result
        $result = [
            'id'   => 'total',
            'name' => 'total'
        ];

        $selectFields = [];
        foreach ($completenessFields as $alias => $field) {
            $selectFields[] = 'AVG(' . Util::fromCamelCase($field) . ') AS `' . $alias . '`';
        }

        $sql = "SELECT " . implode($selectFields, ', ') . " FROM product WHERE deleted = 0";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        foreach ($sth->fetch(\PDO::FETCH_ASSOC) as $key => $value) {
            $result[$key] = round($value, 2);
        }

        return $result;
    }

    /**
     * Get language key by field name
     *
     * @param string $fieldName
     *
     * @return string
     * @throws Error
     */
    protected function getLanguageKey(string $fieldName): string
    {
        $result = 'default';

        $inputLanguages = $this->getConfig()->get('inputLanguageList') ?? [];

        $str = substr($fieldName, -4);

        foreach ($inputLanguages as $lang) {
            $preparedLang = ucfirst(Util::toCamelCase(strtolower($lang)));

            if ($preparedLang === $str) {
                $result = $lang;
            }
        }

        return $result;
    }

    /**
     * Get metadata
     *
     * @return Metadata
     * @throws Error
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }

    /**
     * Get product channel data
     *
     * @return array
     */
    protected function getProductChannelData(): array
    {
        $sql = "
            SELECT
              channel.id AS channelId,
              channel.name AS channelName,
              pc.product_id AS productId
            FROM product_channel pc
            JOIN channel ON channel.id = pc.channel_id AND channel.deleted = 0
            WHERE pc.deleted = 0
        ";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
