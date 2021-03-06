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

Espo.define('completeness:views/record/panels/complete-side', 'views/record/panels/side',
    Dep => Dep.extend({

        setupFields() {
            Dep.prototype.setupFields.call(this);

            this.fieldList = this.getCompleteFields();
        },

        reCreateFields() {
            const fields = this.getFieldViews();
            $.each(fields, (name, view) => view.remove());
            this.setup();
            this.reRender();
        },

        getCompleteFields() {
            const fields = this.getMetadata().get(['entityDefs', this.model.name, 'fields']) || {};

            let completeFields = [];

            $.each(fields, (name, defs) => {
                if (
                    defs.isCompleteness
                        && !defs.multilangField
                        && this.model.get(name) !== null
                        && typeof this.model.get(name) !== 'undefined'
                ) {
                    completeFields.push({name: name});
                }
            });

            //add multi-language complete fields after main complete field
            if (this.getConfig().get('isMultilangActive')) {
                (this.getConfig().get('inputLanguageList') || []).forEach(lang => {
                    const multiComplete = lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), 'complete');
                    completeFields.push({name: multiComplete});
                });
            }

            completeFields = completeFields.sort((a, b) => (fields[a.name] || {}).sortOrder - (fields[b.name] || {}).sortOrder);

            return completeFields;
        }

    })
);

