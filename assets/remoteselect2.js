/**
 * Support for SelectBoxRemoteControl in Select2.
 * https://select2.github.io/
 * @node-attr data-data-url Zdroj dat. { items, total, isMoreResults, page }
 */
const PAGE_SIZE = 10;
export function isRemoteSelect(el) {
    if (!(el instanceof HTMLSelectElement)) {
        return false;
    }
    return el.dataset.type === 'remoteselect' || el.dataset.class === 'filterable';
}
/**
 * @param overrides Extra Select2 options merged over the defaults computed from
 * the element's data-attributes (e.g. `{ tags: true }` to allow creating new records).
 */
export function initSelect2Impl(el, overrides = {}) {
    if (!isRemoteSelect(el)) {
        return;
    }
    const isRemote = el.dataset.type === 'remoteselect';
    const isFilterable = el.dataset.class === 'filterable';
    const options = {};
    if (isRemote) {
        options.ajax = {
            url: () => el.dataset.dataUrl ?? '',
            dataType: 'json',
            delay: 250,
            data: (params) => ({
                term: params.term,
                page: params.page,
            }),
            processResults: (data, params) => {
                params.page = params.page || 1;
                return {
                    results: data.items.map((x) => ({ ...x, id: x.id, text: x.label })),
                    pagination: {
                        more: params.page * PAGE_SIZE < data.total,
                    },
                };
            },
        };
    }
    if (!isFilterable) {
        options.minimumResultsForSearch = -1;
    }
    jQuery(el).select2({ ...options, ...overrides });
}
