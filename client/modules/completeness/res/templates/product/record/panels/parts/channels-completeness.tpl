{{#each channelData}}
<div class="cell form-group col-sm-6 col-md-12" data-name="channel-complete" data-id="{{id}}">
    <label class="control-label" data-name="channel-complete" data-id="{{id}}" style="cursor: pointer;">
        <span class="label-text">{{name}}</span>
    </label>
    <div class="field" data-name="channel-complete" data-id="{{id}}">
        <div class="completeness general">
            <span class="progress-value">{{value}}%</span>
            <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{progressBarValue}}%"></div>
            </div>
        </div>
    </div>
</div>
{{/each}}