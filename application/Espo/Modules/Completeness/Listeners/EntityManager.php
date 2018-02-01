<?php
declare(strict_types = 1);

namespace Espo\Modules\Completeness\Listeners;

use Espo\Modules\TreoCrm\Listeners\AbstractListener;

/**
 * EntityManager listener
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManager extends AbstractListener
{
    /**
     * @var array
     */
    protected $scopesConfig = null;

    /**
     * Before update action
     *
     * @param array $data
     *
     * @return void
     */
    public function beforeUpdate(array $data)
    {
        // update scopes
        $this
            ->getContainer()
            ->get('metadata')
            ->set('scopes', $data['name'], $this->getPreparedScopesData($data['data']));
    }

    /**
     * After update action
     *
     * @param array $data
     *
     * @return void
     */
    public function afterUpdate(array $data)
    {
        if (!empty($data['data']['hasCompleteness'])) {
            // recalc complete param
            $this
                ->container
                ->get('serviceFactory')
                ->create('Completeness')
                ->recalcEntity($data['name']);
        }
    }

    /**
     * Get prepared scopes data
     *
     * @param array $data
     *
     * @return array
     */
    protected function getPreparedScopesData(array $data): array
    {
        $scopeData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->getScopesConfig()['edited'])) {
                $scopeData[$key] = $value;
            }
        }

        return $scopeData;
    }

    /**
     * Get scopes config
     *
     * @return array
     */
    protected function getScopesConfig(): array
    {
        if (is_null($this->scopesConfig)) {
            // prepare result
            $this->scopesConfig = [];

            foreach ($this->getContainer()->get('metadata')->getModuleList() as $module) {
                // prepare file
                $file = sprintf('application/Espo/Modules/%s/Configs/Scopes.php', $module);

                if (file_exists($file)) {
                    $this->scopesConfig = array_merge_recursive($this->scopesConfig, include $file);
                }
            }
        }

        return $this->scopesConfig;
    }
}
