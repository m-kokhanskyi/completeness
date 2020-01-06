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

Espo.define('completeness:views/fields/base', 'class-replace!completeness:views/fields/base', function (Dep) {

    return Dep.extend({

        init: function () {
            Dep.prototype.init.call(this);

            if (this.getMetadata().get(['scopes', this.model.name, 'hasCompleteness'])
                || this.getMetadata().get(['entityDefs', this.model.name, 'fields', this.name, 'hasCompleteValidation'])) {
                this.validations = Espo.Utils.clone(this.validations);
                if (this.validations.includes('required')) {
                    this.validations.splice(this.validations.indexOf('required'), 1);
                }
            }
        },
    });
});

