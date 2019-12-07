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
        },

        data() {
            let data = Dep.prototype.data.call(this);

            data.value = this.roundNumber(data.value);
            data.valueLabel = this.formatNumber(data.value);

            let inputLanguageList = this.getConfig().get('inputLanguageList') || [];
            if (this.getConfig().get('isMultilangActive') && inputLanguageList.length && this.getMetadata().get(['entityDefs', data.scope, 'fields', data.name, 'isMultilang'])) {
                data.valueList = inputLanguageList.map((lang, i) => {
                    let local = lang.split('_').reduce((prev, curr) => prev + Espo.Utils.upperCaseFirst(curr.toLocaleLowerCase()), '');
                    let name = data.name + local;
                    let value = this.roundNumber(this.model.get(name));
                    return {
                        name: name,
                        value: value,
                        valueLabel: this.formatNumber(value),
                        isNotEmpty: value !== null && value !== '',
                        shortLang: lang,
                        customLabel: this.options.customLabel,
                        index: i
                    }

                });
            }
            return data;
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
        },

        formatNumber(value) {
            if (value !== null) {
                let parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                return parts.join(this.decimalMark);
            }
            return '';
        },
    })
);