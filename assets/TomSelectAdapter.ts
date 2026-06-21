/**
 * Support for SelectBoxRemoteControl in TomSelect.
 * https://tom-select.js.org/
 * @node-attr data-data-url Zdroj dat. { items, total, isMoreResults, page }
 */

interface RemoteSelectItem {
	id: string;
	label: string;
	/** Extra decoration fields (e.g. `flag`, `icon`, `description`) picked up by custom renderers. */
	[key: string]: unknown;
}

interface RemoteSelectResponse {
	items: RemoteSelectItem[];
	total: number;
	isMoreResults: boolean;
	page: number;
}

function isRemoteSelect(el: Element): el is HTMLSelectElement {
	if (!(el instanceof HTMLSelectElement)) {
		return false;
	}
	return el.dataset.type === 'remoteselect' || el.dataset.class === 'filterable';
}

function buildRemoteUrl(base: string, term: string, page: number): string {
	const url = new URL(base, window.location.origin);
	url.searchParams.set('term', term);
	url.searchParams.set('page', String(page));
	return url.toString();
}

interface TomSelectLoadApi {
	getUrl(query: string): string;
	setNextUrl(query: string, url: string): void;
}

export interface TomSelectSettings {
	valueField?: string;
	labelField?: string;
	searchField?: string[];
	plugins?: string[];
	preload?: boolean | 'focus';
	controlInput?: string | null;
	firstUrl?: (query: string) => string;
	load?: (this: TomSelectLoadApi, query: string, callback: (results?: RemoteSelectItem[]) => void) => void;
	/** Any other TomSelect setting (e.g. `create`, `maxItems`, ...), not exhaustively typed here. */
	[key: string]: unknown;
}

declare class TomSelect {
	constructor(el: Element, settings?: TomSelectSettings);
}

/**
 * @param overrides Extra TomSelect settings merged over the defaults computed from
 * the element's data-attributes (e.g. `{ plugins: ['drag_drop'], create: true }`).
 * `plugins` are concatenated rather than replaced, so `virtual_scroll` stays enabled
 * for remote fields even when you add your own plugins.
 */
export function initTomSelectImpl(el: Element, overrides: TomSelectSettings = {}): void {
	if (!isRemoteSelect(el)) {
		return;
	}

	const isRemote = el.dataset.type === 'remoteselect';
	const isFilterable = el.dataset.class === 'filterable';

	const settings: TomSelectSettings = {
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
				.then((data: RemoteSelectResponse) => {
					if (data.isMoreResults) {
						this.setNextUrl(query, buildRemoteUrl(dataUrl, query, data.page + 1));
					}
					callback(data.items);
				})
				.catch(() => callback());
		};
	}

	const plugins = [...(settings.plugins ?? []), ...((overrides.plugins as string[] | undefined) ?? [])];

	new TomSelect(el, { ...settings, ...overrides, plugins });
}
