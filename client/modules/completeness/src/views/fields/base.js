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

Espo.define('completeness:views/fields/base', 'class-replace!completeness:views/fields/base', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if(this.getMetadata().get('scopes.' + this.model.name + '.hasCompleteness')) {
                this.validations = Espo.Utils.clone(this.validations);
                if (this.validations.includes('required')) {
                    this.validations.splice(this.validations.indexOf('required'), 1);
                }
            }
        },

        validate: function () {
            let validate = false;
            if (this.name === "isActive") {
                let inputLanguageList = this.getConfig().get('inputLanguageList') || [];
                let langNameCompleteness = ['complete'];
                if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                    langNameCompleteness.push(...inputLanguageList.map(lang => 'complete' + lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), '')));
                }
                validate = langNameCompleteness.some(_complete => parseInt(this.model.attributes[_complete]) < 100);
            }
            for (var i in this.validations) {
                var method = 'validate' + Espo.Utils.upperCaseFirst(this.validations[i]);
                if (this[method].call(this)) {
                    this.trigger('invalid');
                    validate =  true;
                }
            }
            return validate;
        },

        afterNotValidate: function () {
            if (this.name === "isActive" ) {
                var msg = this.translate('fieldCompletenessShouldFill', 'messages');
                this.notify(msg, 'error');
                this.$element[0].checked = false;
            } else {
                this.notify('', 'error');
            }
            model.set(prev, {silent: true});
            return;
        },
    });
});

