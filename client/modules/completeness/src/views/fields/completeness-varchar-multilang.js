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

Espo.define('completeness:views/fields/completeness-varchar-multilang', 'multilang:views/fields/varchar-multilang',
    Dep => Dep.extend({

        listTemplate: 'completeness:fields/completeness-varchar-multilang/list',

        detailTemplate: 'completeness:fields/completeness-varchar-multilang/detail',

        setup() {
            Dep.prototype.setup.call(this);

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark');
            } else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark');
                }
            }
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.value = Math.round(this.formatNumber(data.value) * 100) / 100;
            data.valueList = this.langFieldNameList.map((name, i) => {
                let value = Math.round(this.formatNumber(this.model.get(name)) * 100) / 100;
                return {
                    name: name,
                    value: value,
                    isNotEmpty: value !== null && value !== '',
                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    customLabel: this.options.customLabel,
                    index: i
                }
            });
            return data;
        },

        afterRender() {
            if (this.mode === 'detail' || this.mode === 'list') {
                if (parseFloat(this.model.get(this.name)) === 0) {
                    this.$el.find('.completeness.general .progress-value').addClass('none');
                }
            }
            if (this.mode === 'detail') {
                this.langFieldNameList.forEach((lang, i) => {
                    if (parseFloat(this.model.get(lang)) === 0) {
                        this.$el.find(`.completeness.list-elem-${i} .progress-value`).addClass('none');
                    }
                });
            }
        },

        formatNumber: function (value) {
            if (this.disableFormatting) {
                return value;
            }
            if (value) {
                let parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                return parts.join(this.decimalMark);
            }
            return '';
        },
    })
);