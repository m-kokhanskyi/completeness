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

Espo.define('completeness:views/dashlets/channel-completeness', ['views/dashlets/abstract/base','lib!Flotr'],
    (Dep, Flotr) => Dep.extend({

        template: 'completeness:dashlets/channel-completeness',

        url: 'Dashlet/CompletenessOverview',

        name: 'ChannelCompleteness',

        decimalMark: '.',

        thousandSeparator: '',

        colorList: ['#6FA8D6', '#4E6CAD', '#EDC555', '#ED8F42', '#DE6666', '#7CC4A4', '#8A7CC2', '#D4729B'],

        textColor: '#333333',

        hoverColor: '#FF3F19',

        chartData: null,

        chartContainer: null,

        init: function () {
            Dep.prototype.init.call(this);

            this.colorList = this.getThemeManager().getParam('chartColorList') || this.colorList;
            this.textColor = this.getThemeManager().getParam('textColor') || this.textColor;
            this.hoverColor = this.getThemeManager().getParam('hoverColor') || this.hoverColor;

            this.decimalMark = this.getPreferenceValue('decimalMark') || this.decimalMark;
            this.thousandSeparator = this.getPreferenceValue('thousandSeparator') || this.thousandSeparator;

            this.listenToOnce(this, 'after:render', () => {
                $(window).on('resize.chart' + this.name, () => {
                    this.draw();
                });
            });

            this.listenToOnce(this, 'remove', () => {
                $(window).off('resize.chart' + this.name)
            });
        },

        afterRender() {
            this.chartContainer = this.$el.find('.chart-container');

            this.buildChart();
        },

        buildChart() {
            this.fetch().then(data => {
                this.chartData = this.prepareData(data);

                this.draw();
            });
        },

        getPreferenceValue(key) {
            if (this.getPreferences().has(key)) {
                return this.getPreferences().get(key)
            } else if (this.getConfig().has(key)) {
                return this.getConfig().get(key);
            }
            return null;
        },

        getUrl() {
            return this.url;
        },

        fetch() {
            return this.ajaxGetRequest(this.getUrl());
        },

        prepareData(data) {
            let preparedData = {
                primary: [],
                secondary: []
            };
            (data.list || []).forEach(item => {
                if (item.id === 'total') {
                    preparedData.primary = {
                        id: item.id,
                        title: this.translate('Total', 'labels', 'Global'),
                        value: item.default,
                        data: [
                            {
                                label: this.translate('Total', 'labels', 'Global'),
                                data: [[0, item.default]]
                            },
                            {
                                label: '',
                                data: [[0, 100 - item.default]]
                            }
                        ]
                    };
                } else if (item.default) {
                    preparedData.secondary.push({
                        id: item.id,
                        title: item.name,
                        value: item.default,
                        data: [
                            {
                                label: item.name,
                                data: [[0, item.default]]
                            },
                            {
                                label: '',
                                data: [[0, 100 - item.default]]
                            }
                        ]
                    });
                }
            });
            return preparedData;
        },

        draw() {
            this.drawSingleChart(this.chartContainer.find('.primary').get(0), this.chartData.primary);

            this.buildSecondaryContainers();
            this.chartData.secondary.forEach(item => {
                this.drawSingleChart(this.chartContainer.find(`.secondary [data-id="${item.id}"]`).get(0), item);
            });
        },

        drawSingleChart(elem, serie) {
            let self = this;
            let configuration = Espo.Utils.cloneDeep(this.getDefaultChartConfiguration());
            configuration.title = serie.title;
            configuration.pie.labelFormatter = function (total, value) {
                if (value === serie.value) {
                    return `<span class="small" style="font-size: 0.8em; color:${self.textColor}">${self.formatNumber(value)}%</span>`;
                } else {
                    return '';
                }
            };

            Flotr.draw(elem, serie.data, configuration);
        },

        buildSecondaryContainers() {
            let containerWidth = this.chartContainer.find('.secondary').width();
            let containerHeight = this.chartContainer.find('.secondary').height();
            let count = this.chartData.secondary.length;
            if (count > 0) {
                let divider = 1;
                let done = false;
                while (!done) {
                    let slots = Math.floor(containerWidth / (containerHeight / divider)) * Math.floor(divider);
                    if (count <= slots) {
                        done = true;
                    } else {
                        divider += 0.1;
                    }
                }

                let elemHeight = Math.floor(containerHeight / divider);
                this.chartContainer.find('.secondary').html('');
                this.chartData.secondary.forEach(item => {
                    this.chartContainer.find('.secondary').append($('<div></div>').attr('data-id', item.id).css({display: 'inline-block', width: elemHeight, height: elemHeight}));
                });
            }
        },

        formatNumber(value) {
            if (value !== null) {
                let parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                return parts.join(this.decimalMark);
            }
            return '';
        },

        actionRefresh: function () {
            this.buildChart();
        },

        getDefaultChartConfiguration() {
            return {
                colors: this.colorList,
                shadowSize: false,
                pie: {
                    show: true,
                    explode: 0,
                    lineWidth: 1,
                    fillOpacity: 1,
                    sizeRatio: 0.8
                },
                grid: {
                    horizontalLines: false,
                    verticalLines: false,
                    outline: '',
                },
                yaxis: {
                    showLabels: false,
                },
                xaxis: {
                    showLabels: false,
                },
                mouse: {
                    track: false,
                },
                legend: {
                    show: false
                }
            };
        }

    })
);

