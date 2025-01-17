
class MahoDashboard
{

    constructor(config = {}) {
        this.config = config;

        console.log(config)

        document.addEventListener('DOMContentLoaded', this.bindEventListeners.bind(this));
    }

    bindEventListeners() {
        document.getElementById(this.config.diagrams?.switcherId)?.addEventListener('change', (event) => {
            this.changeDiagramsPeriod(event.target);
        });
    }

    async changeDiagramsPeriod(periodSwitcherEl) {
        if (!Array.isArray(this.config.diagrams?.tabs)) {
            return;
        }

        for (const tabId of this.config.diagrams.tabs) {
            const html = await mahoFetch(setRouteParams(this.config.diagrams.ajaxUrl, {
                block: `tab_${tabId}`,
                period: periodSwitcherEl.value,
            }));

            const tabContentEl = document.getElementById(`${this.config.diagrams.htmlId}_${tabId}_content`);
            updateElementHtmlAndExecuteScripts(tabContentEl, html);
        }

        const html = await mahoFetch(setRouteParams(this.config.diagrams.ajaxUrl, {
            block: 'totals',
        }));

        const tabContentEl = document.getElementById('dashboard_diagram_totals');
        updateElementHtmlAndExecuteScripts(tabContentEl, html);
    }

    initializeChart(canvasId, datasets, labels) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            return;
        }

        const borderColor = '#adb41a';
        const backgroundColor = canvas.getContext('2d').createLinearGradient(0, 0, 0, 293);
        backgroundColor.addColorStop(0, `${borderColor}aa`);
        backgroundColor.addColorStop(1, `${borderColor}00`);


        const plugin = {
            afterInit: (chart, args, opts) => {
                chart.hoverState = { x: 0, draw: false };
            },
            afterEvent: (chart, args) => {
                chart.hoverState = { x: args.event.x, draw: args.inChartArea}
                chart.draw()
            },
            beforeDatasetsDraw: (chart, args, opts) => {
                if (!chart.hoverState.draw) {
                    return;
                }

                const ctx = chart.ctx;
                ctx.save()
                ctx.beginPath()
                ctx.lineWidth = 0.25;
                ctx.strokeStyle = 'black';
                ctx.moveTo(chart.hoverState.x, chart.chartArea.bottom)
                ctx.lineTo(chart.hoverState.x, chart.chartArea.top)
                ctx.stroke()
                ctx.restore()
            }
        }

        Chart.Tooltip.positioners.cursor = (_, coordinates) => coordinates;

        const config = {
            type: 'line',
            data: {
                labels: labels.map((date) => {
                    return date;
                    const year = date.substr(3, 6);
                    const month = date.substr(0, 2);
                    return new Date(year, month - 1);
                }),
                datasets: Object.entries(datasets).map(([id, data]) => ({
                    id,
                    data,
                    borderColor,
                    fill: true,
                    backgroundColor,
                    pointRadius: 0,
                    tension: 0.2,
                })),
            },
            options: {
                interaction: {
                    intersect: false,
                    mode: 'index',
                    position: 'cursor',
                },
                scales: {
                    x: {
                        //type: 'time',
                        grid: {
                            //display : false,
                        },
                    },
                    y: {
                        suggestedMin: 0,
                        suggestedMax: 10,
                        ticks: {
                            // Include a dollar sign in the ticks
                            callback: function(value, index, ticks) {
                                //console.log(value, index, ticks)
                                return '$' + value;
                            }
                        },
                    },
                },
                animation: {
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        animation: false,
                    },
                },
            },
            plugins: [plugin],
        };
        new Chart(canvasId, config);
    }

}
