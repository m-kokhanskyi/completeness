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

Espo.define('completeness:views/fields/completeness-float', 'views/fields/float',
    Dep => Dep.extend({

        listTemplate: 'completeness:fields/completeness-float/list',

        detailTemplate: 'completeness:fields/completeness-float/detail',

        data() {
            return _.extend({
                progressBarValue: this.roundNumber(this.model.get(this.name))
            }, Dep.prototype.data.call(this));
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'detail' || this.mode === 'list') {
                if (parseFloat(this.model.get(this.name)) === 0) {
                    this.$el.find('.completeness.general .progress-value').addClass('none');
                }
            }
        },

        roundNumber(value) {
            return Math.round(value * 100) / 100;
        }

    })
);