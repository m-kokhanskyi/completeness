/*
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of Zinit Solutions GmbH and is protected
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

Espo.define('completeness:views/record/detail', 'class-replace!completeness:views/record/detail',
    Dep => Dep.extend({

        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            } else {
                if (this.model.hasChanged('isActive') && this.model.get('isActive') === 0) {
                    let msg = this.translate('activationFailed', 'exceptions', 'Completeness');
                    this.notify(msg, 'error');
                } else {
                    this.notify('Saved', 'success');
                }
            }
            this.enableButtons();
            this.setIsNotChanged();
        },
    })
);