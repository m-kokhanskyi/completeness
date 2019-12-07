<div class="completeness general">
    {{#if isNotEmpty}}
    <span class="progress-value">{{valueLabel}}%</span>
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
    </div>
    {{else}}
    {{translate 'None'}}
    {{/if}}
</div>
