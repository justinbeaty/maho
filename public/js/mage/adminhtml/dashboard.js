
class MahoDashboard
{

    constructor(config = {}) {
        this.config = config;
    }

    async changeDiagramsPeriod(periodObj) {
        periodParam = periodObj.value ? 'period/' + periodObj.value + '/' : '';

        for (const tabId of this.config.tabIds) {
            const html = await mahoFetch(setRouteParams(this.config.ajaxUrl, {
                block: `tab_${tabId}`,
                period: periodObj.value,
            }));

            const tabContentEl = document.getElementById(`${this.id}_${tabId}_content`);
            updateElementHtmlAndExecuteScripts(tabContentEl, html);
        }

        const html = await mahoFetch(setRouteParams(this.config.ajaxUrl, {
            block: 'totals',
        }));

        const tabContentEl = document.getElementById('dashboard_diagram_totals');
        updateElementHtmlAndExecuteScripts(tabContentEl, html);
    }

}
