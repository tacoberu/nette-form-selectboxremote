/**
 * Support for SelectBoxRemoteControl in Select2.
 * https://select2.github.io/
 * @node-attr data-data-url Zdroj dat. { items, total, isMoreResults, page }
 */

const PAGE_SIZE = 10;

export interface RemoteSelectItem {
	id: string;
	label: string;
	/** Extra decoration fields (e.g. `flag`, `icon`, `description`) picked up by custom renderers. */
	[key: string]: unknown;
}

export interface RemoteSelectResponse {
	items: RemoteSelectItem[];
	total: number;
	isMoreResults: boolean;
	page: number;
}

export function isRemoteSelect(el: Element): el is HTMLSelectElement {
	if (!(el instanceof HTMLSelectElement)) {
		return false;
	}
	return el.dataset.type === 'remoteselect' || el.dataset.class === 'filterable';
}

interface Select2Params {
	term?: string;
	page?: number;
}

export interface Select2Options {
	ajax?: {
		url: () => string;
		dataType: string;
		delay: number;
		data: (params: Select2Params) => { term?: string; page?: number };
		processResults: (data: RemoteSelectResponse, params: Select2Params) => {
			results: Array<RemoteSelectItem & { text: string }>;
			pagination: { more: boolean };
		};
	};
	minimumResultsForSearch?: number;
	/** Any other Select2 option (e.g. `tags`, `closeOnSelect`, ...), not exhaustively typed here. */
	[key: string]: unknown;
}

/**
 * Select2 is a jQuery plugin, this is the only point where it is needed.
 */
declare const jQuery: (el: Element) => { select2(options?: Select2Options): void };

/**
 * @param overrides Extra Select2 options merged over the defaults computed from
 * the element's data-attributes (e.g. `{ tags: true }` to allow creating new records).
 */
export function initSelect2Impl(el: Element, overrides: Select2Options = {}): void {
	if (!isRemoteSelect(el)) {
		return;
	}

	const isRemote = el.dataset.type === 'remoteselect';
	const isFilterable = el.dataset.class === 'filterable';

	const options: Select2Options = {};

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
