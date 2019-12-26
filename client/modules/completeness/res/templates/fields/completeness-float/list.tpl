<div class="completeness general">
    {{#if isNotEmpty}}
    <span class="progress-value">{{value}}%</span>
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{progressBarValue}}%"></div>
    </div>
    {{else}}
    {{translate 'None'}}
    {{/if}}
</div>
