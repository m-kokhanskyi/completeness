/*
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) Zinit Solutions GmbH
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

Espo.define('completeness:views/record/panels/default-side', 'class-replace!completeness:views/record/panels/default-side', function (Dep) {

    return Dep.extend({

        template: 'completeness:record/panels/default-side',

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getMetadata().get('scopes.' + this.model.name + '.hasCompleteness')) {
                this.createField('complete', true);
                this.listenTo(this.model, 'after:save', function () {
                    this.getView('complete').reRender();
                }, this);
                this.listenTo(this.model, 'after:attributesSave', function () {
                    this.model.fetch({
                        success: function () {
                            this.getView('complete').reRender();
                        }.bind(this)
                    });
                }, this)
            }
        },
    });
});

