<?php
/**
 * Completeness
 * Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <http://treopim.com/eula>.
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

namespace Completeness\Services;

use Espo\Core\Utils\Util;
use PDO;
use Treo\Services\AbstractService;

/**
 * Class CompletenessOverviewDashlet
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CompletenessOverviewDashlet extends AbstractService
{
    /**
     * Get dashlet data
     *
     * @return array
     */
    public function getDashlet(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // get completeness fields
        $completenessSelectFields = $this->prepareCompletenessFieldsForSql();

        if (!empty($completenessSelectFields)) {
            // push channel complete
            $result['list'] = $this->getChannelComplete($completenessSelectFields);

            // push total
            $result['list'][] = $this->getCompletenessTotal($completenessSelectFields);

            // prepare total
            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * Get channel complete overview
     *
     * @param string $selectFields
     *
     * @return array
     */
    protected function getChannelComplete(string $selectFields): array
    {
        $sql = "SELECT c.id AS id, c.name AS name, {$selectFields}
                FROM product p
                    RIGHT JOIN product_channel pc ON pc.product_id = p.id AND pc.deleted = 0
                    RIGHT JOIN channel c ON c.id = pc.channel_id AND c.deleted = 0
                WHERE p.deleted = 0 GROUP BY c.id";

        $values = $this->getEntityManager()->nativeQuery($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($values as $i => $item) {
            foreach ($item as $k => $value) {
                if ($k == 'id' || $k === 'name') {
                    $result[$i][$k] = $value;
                } else {
                    $result[$i][$k] = (float)$value;
                }
            }
        }
        return $result;
    }

    /**
     * Get completeness total data
     *
     * @param string $selectFields
     *
     * @return array
     */
    protected function getCompletenessTotal(string $selectFields): array
    {
        // prepare result
        $result = [
            'id'   => 'total',
            'name' => 'total',
        ];
        $sql = "SELECT " . $selectFields . " FROM product WHERE deleted = 0";

        $values = $this->getEntityManager()->nativeQuery($sql)->fetch(PDO::FETCH_ASSOC);
        foreach ($values as $k => $value) {
            $result[$k] = (float)$value;
        }

        return $result;
    }

    /**
     * Get completeness Select field in product
     *
     * @return string
     */
    protected function prepareCompletenessFieldsForSql(): string
    {
        $selectFields[] = 'ROUND(AVG(' . Util::fromCamelCase('complete') . '), 2) AS `' . 'default' . '`';

        foreach ($this->getLanguages() as $local => $lang) {
            $field = 'complete_' . $local;
            $selectFields[] = 'ROUND(AVG(' . $field . '), 2) AS `' . $local . '`';
        }

        return implode($selectFields, ', ');
    }

    /**
     * Get languages
     *
     * @return array
     */
    protected function getLanguages(): array
    {
        $languages = [];
        if (!empty($this->getConfig()->get('isMultilangActive'))) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $languages[$locale] = Util::toCamelCase(strtolower($locale), '_', true);
            }
        }
        return $languages;
    }
}
