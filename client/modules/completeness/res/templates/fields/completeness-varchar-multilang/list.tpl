<style>
    div.completeness {
        display: inline-block;
        float: left;
        clear: left;
        padding: 0 5px;
        border: 1px solid white;
        border-radius: 25px;
    }
    div.completeness label span {
        color: #000;
    }
    .green {
        background-color: #b3ffb3;
    }
    .orange {
        background-color: #ffc966
    }
    .red {
        background-color: #ff8080;
    }
    .red .progress-value {
        color: #000;
    }
    .orange .progress-bar {
        min-width: 50%;
    }
</style>
<div class="completeness general">
    {{#if isNotEmpty}}
    <span class="progress-value">{{value}}%</span>
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
    </div>
    {{else}}
    {{translate 'None'}}
    {{/if}}
</div>
