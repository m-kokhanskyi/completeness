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

Espo.define('completeness:views/fields/base', 'views/fields/bool', function (Dep) {

    return Dep.extend({

        inlineEditSave: function () {
            var data = this.fetch();
            var self = this;
            var model = this.model;
            var prev = this.initialAttributes;

            model.set(data, {silent: true});
            data = model.attributes;

            var attrs = false;
            for (var attr in data) {
                if (_.isEqual(prev[attr], data[attr])) {
                    continue;
                }
                (attrs || (attrs = {}))[attr] =    data[attr];
            }

            if (!attrs) {
                this.inlineEditClose();
                return;
            }

            if (this.validate()) {
                this.notify('Not valid', 'error');
                model.set(prev, {silent: true});
                return;
            }

            this.notify('Saving...');
            model.save(attrs, {
                success: function () {
                    self.trigger('after:save');
                    model.trigger('after:save');
                    if (self.name === "" && self.model.hasChanged('isActive') && self.model.get('isActive') === 0) {
                        let msg = self.translate('activationFailed', 'exceptions', 'Completeness');
                        self.notify(msg, 'error');
                    } else {
                        self.notify('Saved', 'success');
                    }
                },
                error: function () {
                    self.notify('Error occured', 'error');
                    model.set(prev, {silent: true});
                    self.render()
                },
                patch: true
            });
            this.inlineEditClose(true);
        }
    });
});

