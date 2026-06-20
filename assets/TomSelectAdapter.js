/**
 * Support for SelectBoxRemoteControl in TomSelect.
 * https://tom-select.js.org/
 * @node-attr data-data-url Zdroj dat. { items, total, isMoreResults, page }
 */
function isRemoteSelect(el) {
    if (!(el instanceof HTMLSelectElement)) {
        return false;
    }
    return el.dataset.type === 'remoteselect' || el.dataset.class === 'filterable';
}
function buildRemoteUrl(base, term, page) {
    const url = new URL(base, window.location.origin);
    url.searchParams.set('term', term);
    url.searchParams.set('page', String(page));
    return url.toString();
}
/**
 * @param overrides Extra TomSelect settings merged over the defaults computed from
 * the element's data-attributes (e.g. `{ plugins: ['drag_drop'], create: true }`).
 * `plugins` are concatenated rather than replaced, so `virtual_scroll` stays enabled
 * for remote fields even when you add your own plugins.
 */
export function initTomSelectImpl(el, overrides = {}) {
    if (!isRemoteSelect(el)) {
        return;
    }
    const isRemote = el.dataset.type === 'remoteselect';
    const isFilterable = el.dataset.class === 'filterable';
    const settings = {
        valueField: 'id',
        labelField: 'label',
        searchField: ['label'],
    };
    if (!isFilterable) {
        settings.controlInput = null;
    }
    if (isRemote) {
        const dataUrl = el.dataset.dataUrl ?? '';
        settings.plugins = ['virtual_scroll'];
        settings.preload = 'focus';
        settings.firstUrl = (query) => buildRemoteUrl(dataUrl, query, 1);
        settings.load = function (query, callback) {
            fetch(this.getUrl(query))
                .then((response) => response.json())
                .then((data) => {
                if (data.isMoreResults) {
                    this.setNextUrl(query, buildRemoteUrl(dataUrl, query, data.page + 1));
                }
                callback(data.items);
            })
                .catch(() => callback());
        };
    }
    const plugins = [...(settings.plugins ?? []), ...(overrides.plugins ?? [])];
    new TomSelect(el, { ...settings, ...overrides, plugins });
}
