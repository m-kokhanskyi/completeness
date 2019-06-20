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

Espo.define('completeness:views/dashlets/completeness-overview', 'views/dashlets/abstract/base',
    Dep => Dep.extend({

        _template: '<div class="list-container">{{{list}}}</div>',

        collectionUrl: 'Dashlet/CompletenessOverview',

        actionRefresh: function () {
            this.collection.fetch();
        },

        afterRender: function () {
            this.getCollectionFactory().create('CompletenessOverviewDashlet', collection => {
                this.collection = collection;

                collection.url = this.collectionUrl;
                collection.maxSize = this.getOption('displayRecords');
                collection.model = collection.model.extend({
                    defs: {
                        fields: {
                            name: {
                                linkEntity: 'Channel'
                            }
                        }
                    }
                });

                let layout = [
                    {
                        name: 'name',
                        link: true,
                        notSortable: true,
                        view: 'pim:views/dashlets/fields/list-link-extended'
                    },
                    {
                        name: 'default',
                        notSortable: true,
                        view: 'pim:views/dashlets/fields/percent-varchar'
                    }
                ];

                (this.getConfig().get('inputLanguageList') || []).forEach(item => {
                    layout.push({
                        name: item,
                        notSortable: true,
                        view: 'pim:views/dashlets/fields/percent-varchar'
                    });
                });

                this.listenToOnce(collection, 'sync', () => {
                    this.createView('list', 'views/record/list', {
                        el: this.getSelector() + ' > .list-container',
                        collection: collection,
                        rowActionsDisabled: true,
                        checkboxes: false,
                        listLayout: layout
                    }, view => {
                        this.listenTo(view, 'after:render', () => {
                            this.updateTotalRow();
                        });
                        view.render();
                    });
                });
                collection.fetch();

            });
        },

        updateTotalRow() {
            let row = this.$el.find('tr[data-id="total"]');
            row.children('td').each((index, elem) => {
                if ($(elem).data('name') === 'name') {
                    $(elem).html(`<b>${this.translate('Total', 'labels', 'Global')}</b>`);
                } else {
                    $(elem).html(`<b>${$(elem).html()}</b>`);
                }
            });
        }

    })
);
