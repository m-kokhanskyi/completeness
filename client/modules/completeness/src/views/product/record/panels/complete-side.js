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

Espo.define('completeness:views/product/record/panels/complete-side', 'completeness:views/record/panels/complete-side',
    Dep => Dep.extend({

        template: 'completeness:product/record/panels/complete-side',

        channelCollection: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.waitForView('channelsCompleteness');
            this.getCollectionFactory().create(null, collection => {
                this.channelCollection = collection;
                this.updateChannelCollection();
                this.createChannelsCompletenessView();
            });

            this.listenTo(this.model, 'after:save after:attributesSave', () => {
                this.model.fetch().then(response => this.updateChannelCollection());
            });
            this.listenTo(this.model, 'after:relate after:unrelate', data => {
                if (data === 'channels') {
                    this.model.fetch().then(response => this.updateChannelCollection());
                }
            });
        },

        updateChannelCollection() {
            this.channelCollection.reset();
            let data = this.model.get('channelCompleteness') || {};
            this.channelCollection.add(data.list || []);
            this.channelCollection.total = data.total || 0;
        },

        createChannelsCompletenessView() {
            this.createView('channelsCompleteness', 'completeness:views/product/record/panels/parts/channels-completeness', {
                collection: this.channelCollection,
                el: `${this.options.el} .channels-completeness`
            }, view => {
                // view.render();
            });
        }
    })
);
