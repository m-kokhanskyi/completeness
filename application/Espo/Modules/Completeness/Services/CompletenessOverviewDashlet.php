<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) Zinit Solutions GmbH
 *
 * This Software is the property of Zinit Solutions GmbH and is protected
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

namespace Espo\Modules\Completeness\Services;

use Espo\Core\Utils\Metadata;
use Espo\Modules\Pim\Services\AbstractProductsByChannelsDashlet;
use Espo\Modules\TreoCore\Core\Utils\Config;
use \Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Util;

/**
 * Class CompletenessOverviewDashlet
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class CompletenessOverviewDashlet extends AbstractProductsByChannelsDashlet
{

    /**
     * Int Class
     */
    protected function init()
    {
        parent::init();

        $this->addDependencyList([
            'metadata',
            'config',
            'language'
        ]);
    }

    /**
     * Get dashlet data
     *
     * @return array
     */
    public function getDashlet(): array
    {
        $result = ['total' => 0, 'list' => []];
        $productFields = [];

        $completenessFields = $this->getCompletenessFieldInProduct();

        foreach ($completenessFields as $key => $fieldName) {
            $productFields[Util::fromCamelCase($fieldName)] = $key;
        }

        if (count($completenessFields) > 0) {
            foreach ($this->getProductsWithCategoryByChannel($productFields) as $channelId => $channelData) {
                // get channel complete
                $data = $this->getChannelComplete($channelData, $completenessFields);

                // prepare channel data
                $data['id'] = (string)$channelId;
                $data['name'] = (string)$channelData['channel']->get('name');

                $result['list'][] = $data;
            }

            $result['list'][] = $this->getCompletenessTotal($completenessFields);

            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * Get completeness field in product
     *
     * @return array
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
     * Get completeness total data
     *
     * @param array $completenessFields
     *
     * @return array
     */
    protected function getCompletenessTotal(array $completenessFields): array
    {
        $result = ['id' => 'total', 'name' => 'total'];

        $selectFields = [];

        foreach ($completenessFields as $alias => $field) {
            $selectFields[] = 'AVG(' . Util::fromCamelCase($field) . ') AS `' . $alias . '`';
        }

        $sql = "SELECT " . implode($selectFields, ', ') . " FROM product WHERE deleted = 0";

        $sth = $this->getPDO()->prepare($sql);
        $sth->execute();

        foreach ($sth->fetch(\PDO::FETCH_ASSOC) as $key => $value) {
            $result[$key] = round($value, 2);
        }

        return $result;
    }


    /**
     * Get config
     *
     * @return Config
     * @throws Error
     */
    protected function getConfig(): Config
    {
        return $this->getInjection('config');
    }

    /**
     * Get metadata
     *
     * @return Metadata
     * @throws Error
     */
    protected function getMetadata(): Metadata
    {
        return $this->getInjection('metadata');
    }

    /**
     * Get language key by field name
     *
     * @param string $fieldName
     *
     * @return string
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
     * Get channel complete overview
     *
     * @param array $channelData
     * @param array $completenessFields
     *
     * @return array
     */
    protected function getChannelComplete(array $channelData, array $completenessFields): array
    {
        $result = [];

        $productNumber = 0;
        $completenessData = [];

        // prepare complete data
        foreach ($completenessFields as $key => $fieldName) {
            $completenessData[$key] = null;
        }

        // calc complete sum
        if (!empty($channelData['products'])) {
            foreach ($channelData['products'] as $productData) {
                foreach ($completenessData as $fieldName => $value) {
                    $complete = $productData[$fieldName] ?? null;

                    if ($complete !== null && $completenessData[$fieldName] === null) {
                        $completenessData[$fieldName] = (float)$complete;
                    } elseif ($complete !== null && $completenessData[$fieldName] !== null) {
                        $completenessData[$fieldName] += (float)$complete;
                    }

                    $productNumber++;
                }
            }
        }

        // prepare result
        foreach ($completenessData as $fieldName => $complete) {
            if ($productNumber > 0 && $complete !== null) {
                $result[$fieldName] = round($complete / $productNumber, 2);
            } else {
                $result[$fieldName] = null;
            }
        }

        return $result;
    }
}
