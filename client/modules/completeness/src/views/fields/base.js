Espo.define('completeness:views/fields/base', 'class-replace!completeness:views/fields/base', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if(this.getMetadata().get('scopes.' + this.model.name + '.hasCompleteness')) {
                this.validations.splice(this.validations.indexOf('required'), 1);
            }
        },
    });
});

