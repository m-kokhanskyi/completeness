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

        setup: function () {
            this.type = this.mode;
            if ('type' in this.options) {
                this.type = this.options.type;
            }

            if (this.defaultPanel) {
                this.setupDefaultPanel();
            }

            if (this.getMetadata().get('scopes.' + this.scope + '.hasCompleteness') && ['detail', 'detailSmall'].includes(this.type)) {
                this.setupCompletenessPanel();
            }

            this.setupPanels();

            var additionalPanels = this.getMetadata().get('clientDefs.' + this.scope + '.sidePanels.' + this.type) || [];
            additionalPanels.forEach(function (panel) {
                this.panelList.push(panel);
            }, this);

            this.panelList = this.panelList.filter(function (p) {
                if (p.aclScope) {
                    if (!this.getAcl().checkScope(p.aclScope)) {
                        return;
                    }
                }
                if (p.accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(p.accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }
                return true;
            }, this);

            this.panelList = this.panelList.map(function (p) {
                var item = Espo.Utils.clone(p);
                if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                    item.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                } else {
                    this.recordHelper.setPanelStateParam(p.name, item.hidden || false);
                }
                return item;
            }, this);

            this.wait(true);
            this.getHelper().layoutManager.get(this.scope, 'sidePanels' + Espo.Utils.upperCaseFirst(this.type), function (layoutData) {
                if (layoutData) {
                    this.alterPanels(layoutData);
                }

                if (this.streamPanel && this.getMetadata().get('scopes.' + this.scope + '.stream') && this.getConfig().get('isStreamSide') && !this.model.isNew()) {
                    this.setupStreamPanel();
                }

                this.setupPanelViews();
                this.wait(false);
            }.bind(this));
        },

        setupCompletenessPanel() {
            const view = this.getMetadata().get(['clientDefs', this.model.name, 'completenessPanelView']) || 'completeness:views/record/panels/complete-side';

            const completenessPanelDefs = {
                name: 'complete',
                label: 'Complete',
                view: view,
                fieldList: this.getCompleteFields()
            };

            this.panelList.push(completenessPanelDefs);
        },

        getCompleteFields() {
            const fields = this.getMetadata().get(['entityDefs', this.model.name, 'fields']) || {};

            let completeFields = [];
            $.each(fields, (name, defs) => {
                if (defs.isCompleteness && !defs.multilangField) {
                    completeFields.push({name: name});
                }
            });

            completeFields = completeFields.sort((a, b) => (fields[a.name] || {}).sortOrder - (fields[b.name] || {}).sortOrder);

            //add multi-language complete fields after main complete field
            if (this.getConfig().get('isMultilangActive')) {
                const multiCompleteFields = [];
                (this.getConfig().get('inputLanguageList') || []).forEach(lang => {
                    const multiComplete = lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), 'complete');
                    multiCompleteFields.push({name: multiComplete});
                });
                const index = completeFields.findIndex(item => item.name === 'complete');
                completeFields.splice(index + 1, 0, ...multiCompleteFields);
            }

            return completeFields;
        }
    })
});