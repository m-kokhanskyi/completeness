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

namespace Completeness\Listeners;

use Pim\Entities\ProductFamilyAttribute;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class ProductFamilyAttributeEntity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductFamilyAttributeEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        /** @var ProductFamilyAttribute $productFamilyAttribute */
        $productFamilyAttribute = $event->getArgument('entity');
        if ($productFamilyAttribute->isNew() && !empty($productFamilyAttribute->get('isRequired'))) {
            $this->updateCompletenessProduct((string)$productFamilyAttribute->get('productFamilyId'));
        } elseif (!$productFamilyAttribute->isNew()) {
            $this->updateCompletenessProduct((string)$productFamilyAttribute->get('productFamilyId'));
        }
    }

    /**
     * @param string $productFamilyId
     */
    public function updateCompletenessProduct(string $productFamilyId): void
    {
        if ($this->hasCompleteness('Product')) {
            $typesProduct = $this->getProductTypes();
            $this
                ->getService('Completeness')
                ->recalcEntities('Product', ['productFamilyId' => $productFamilyId, 'type' => $typesProduct]);
        }
    }

    /**
     * Get product types
     *
     * @return array
     */
    protected function getProductTypes(): array
    {
        return array_keys($this->getContainer()->get('metadata')->get('pim.productType', []));
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        return $this->getService('Completeness')->hasCompleteness($entityName);
    }
}
