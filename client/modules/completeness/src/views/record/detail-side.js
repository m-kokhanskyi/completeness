/*
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

Espo.define('completeness:views/record/detail-side', 'class-replace!completeness:views/record/detail-side', function (Dep) {

    return Dep.extend({

        setupPanels() {
            Dep.prototype.setupPanels.call(this);

            if (this.getMetadata().get('scopes.' + this.scope + '.hasCompleteness') && ['detail', 'detailSmall'].includes(this.type)) {
                this.setupCompletenessPanel();
            }
        },

        setupCompletenessPanel() {
            const view = this.getMetadata().get(['clientDefs', this.model.name, 'completenessPanelView']) || 'completeness:views/record/panels/complete-side';

            const completenessPanelDefs = {
                name: 'complete',
                label: 'Completeness',
                view: view,
                fieldList: []
            };

            this.panelList.push(completenessPanelDefs);

            this.listenTo(this.model, 'sync', (model, response) => {
                const updateAndReCreate = () => {
                    model.clear({silent: true});
                    model.set(response, {silent: true});
                    this.getView('complete').reCreateFields();
                };
                if (this.getView('complete')) {
                    updateAndReCreate();
                } else {
                    this.listenToOnce(this, 'after:render', () => {
                        updateAndReCreate();
                    });
                }
            });
        }

    })
});