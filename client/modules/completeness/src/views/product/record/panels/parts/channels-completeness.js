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

        events: {
            'click label[data-name="channel-complete"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                let label = $(e.currentTarget);
                label.find('.caret').toggleClass('caret-up');
                let id = label.data('id');
                this.$el.find(`.multilang-labels[data-id="${id}"]`).toggleClass('hidden');
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.collection, 'update', () => this.reRender());
        },

        data() {
            let langs = this.getConfig().get('inputLanguageList') || [];
            let channelData = [];

            this.collection.each(model => {
                channelData.push({
                    id: model.id,
                    name: model.get('name'),
                    value: this.roundNumber(model.get('complete')),
                    valueLabel: this.formatNumber(this.roundNumber(model.get('complete'))),
                    progressBarClass: this.getProgressBarClass(model.get('complete')),
                    langs: langs.map(lang => {
                        return {
                            name: lang,
                            key: `complete${this.formatLanguage(lang)}`
                        };
                    }).filter(lang => model.has(lang.key)).map(lang => {
                        return {
                            key: lang.key,
                            name: lang.name,
                            value: this.roundNumber(model.get(lang.key)),
                            valueLabel: this.formatNumber(this.roundNumber(model.get(lang.key))),
                            progressBarClass: this.getProgressBarClass(model.get(lang.key)),
                        };
                    })
                });
            });
            return {
                channelData: channelData
            };
        },

        formatLanguage(lang) {
            return lang.split('_').map(part => `${part[0].toUpperCase()}${part.slice(1).toLowerCase()}`).join('');
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

        getProgressBarClass(value) {
            if(value === 100) {
                return 'progress-bar-success';
            } else if (value === 0) {
                return 'progress-bar-danger';
            } else {
                return 'progress-bar-warning';
            }
        }

    })
);
