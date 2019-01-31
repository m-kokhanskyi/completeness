{{#each channelData}}
<div class="cell form-group col-sm-6 col-md-12" data-name="channel-complete" data-id="{{id}}">
    <label class="control-label" data-name="channel-complete" data-id="{{id}}" style="cursor: pointer;">
        <span class="label-text">{{name}}</span>
        {{#if langs.length}}
        <span class="caret"></span>
        {{/if}}
    </label>
    <div class="field" data-name="channel-complete" data-id="{{id}}">
        <div class="completeness general">
            <span class="progress-value">{{valueLabel}}%</span>
            <div class="progress">
                <div class="progress-bar {{progressBarClass}}" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
            </div>
        </div>
        <div class="multilang-labels hidden" data-id="{{id}}">
            {{#each langs}}
            <label class="control-label" data-name="{{key}}">
                <span class="label-text">{{../name}} › {{name}}</span>
            </label>
            <div class="completeness">
                <span class="progress-value">{{valueLabel}}%</span>
                <div class="progress">
                    <div class="progress-bar {{progressBarClass}}" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
                </div>
            </div>
            {{/each}}
        </div>
    </div>
</div>
{{/each}}