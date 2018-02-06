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

