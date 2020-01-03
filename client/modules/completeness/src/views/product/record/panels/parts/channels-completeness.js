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

Espo.define('completeness:views/product/record/panels/parts/channels-completeness', 'view',
    Dep => Dep.extend({

        collection: null,

        template: 'completeness:product/record/panels/parts/channels-completeness',

        setup() {
            Dep.prototype.setup.call(this);

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark');
            } else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark');
                }
            }

            if (this.getPreferences().has('thousandSeparator')) {
                this.thousandSeparator = this.getPreferences().get('thousandSeparator');
            } else {
                if (this.getConfig().has('thousandSeparator')) {
                    this.thousandSeparator = this.getConfig().get('thousandSeparator');
                }
            }

            this.listenTo(this.collection, 'update', () => this.reRender());
        },

        data() {
            let channelData = [];

            this.collection.each(model => {
                channelData.push({
                    id: model.id,
                    name: model.get('name'),
                    value: this.getValueForDisplay(model),
                    progressBarValue: this.roundNumber(model.get('complete'))
                });
            });

            return {
                channelData: channelData
            };
        },

        getValueForDisplay: function (model) {
            var value = isNaN(model.get('complete')) ? null : model.get('complete');
            return this.formatNumber(value);
        },

        roundNumber(value) {
            return Math.round(value * 100) / 100;
        },

        formatNumber(value) {
            if (value !== null) {
                let parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                return parts.join(this.decimalMark);
            }
            return '';
        }

    })
);
