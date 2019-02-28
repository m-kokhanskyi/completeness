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

Espo.define('completeness:views/dashlets/locale-completeness', 'completeness:views/dashlets/channel-completeness',
    Dep => Dep.extend({

        name: 'LocaleCompleteness',

        prepareData(data) {
            let preparedData = {
                primary: [],
                secondary: []
            };
            (data.list || []).forEach(item => {
                if (item.id === 'total') {
                    preparedData.primary = {
                        id: item.id,
                        title: this.translate('Total', 'labels', 'Global'),
                        value: item.default,
                        data: [
                            {
                                label: this.translate('Total', 'labels', 'Global'),
                                data: [[0, item.default]]
                            },
                            {
                                label: '',
                                data: [[0, 100 - item.default]]
                            }
                        ]
                    };
                    (this.getConfig().get('inputLanguageList') || []).forEach(locale => {
                        preparedData.secondary.push({
                            id: locale,
                            title: locale,
                            value: item[locale],
                            data: [
                                {
                                    label: locale,
                                    data: [[0, item[locale]]]
                                },
                                {
                                    label: '',
                                    data: [[0, 100 - item[locale]]]
                                }
                            ]
                        });
                    });
                }
            });
            return preparedData;
        },

    })
);
