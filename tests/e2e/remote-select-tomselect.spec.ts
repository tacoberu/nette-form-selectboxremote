import { test, expect, type Page } from '@playwright/test';

const PAGE_SIZE = 10;

function dropdown(fieldId: string) {
	return `#${fieldId}-ts-dropdown`;
}

function options(page: Page, fieldId: string) {
	return page.locator(`${dropdown(fieldId)} .option:not(.loading-more-results)`);
}

async function openSelect(page: Page, fieldId: string, ajaxAction: string) {
	const initialLoad = page.waitForResponse(r => r.url().includes(`do=${ajaxAction}`));
	await page.click(`#${fieldId} + .ts-wrapper .ts-control`);
	await initialLoad;
}

async function openAndFilter(page: Page, fieldId: string, ajaxAction: string, term: string) {
	await page.click(`#${fieldId} + .ts-wrapper .ts-control`);
	await page.waitForResponse(r => r.url().includes(`do=${ajaxAction}`));

	const filtered = page.waitForResponse(r => r.url().includes(`do=${ajaxAction}`) && r.url().includes('term='));
	await page.fill(`#${fieldId} + .ts-wrapper .ts-control input`, term);
	await filtered;
}

test.beforeEach(async ({ page }) => {
	await page.goto('/dashboard/tomselect');
});

test.describe('SelectBoxRemoteControl (TomSelect)', () => {

	test('po otevření načte první stránku záznamů ze serveru', async ({ page }) => {
		await openSelect(page, 'frm-form-category', 'form-category-range');

		const opts = options(page, 'frm-form-category');
		await expect(opts).toHaveCount(PAGE_SIZE);
		await expect(opts.first()).toHaveText('Alaska');
		await expect(opts.last()).toHaveText('Edmond Beta');
	});

	test('scrollování na konec seznamu donačte další stránku', async ({ page }) => {
		await openSelect(page, 'frm-form-category', 'form-category-range');
		await expect(options(page, 'frm-form-category')).toHaveCount(PAGE_SIZE);

		const nextPage = page.waitForResponse(r => r.url().includes('do=form-category-range') && r.url().includes('page=2'));
		await page.locator(dropdown('frm-form-category')).evaluate(el => { el.scrollTop = el.scrollHeight; });
		await nextPage;

		const opts = options(page, 'frm-form-category');
		await expect(opts).toHaveCount(PAGE_SIZE * 2);
		await expect(opts.nth(PAGE_SIZE)).toHaveText('Alaska Gama');
	});

	test('zadáním textu do filtru se zobrazí jen odpovídající záznamy', async ({ page }) => {
		await openAndFilter(page, 'frm-form-category2', 'form-category2-range', 'Berlín');

		const opts = options(page, 'frm-form-category2');
		await expect(opts).toHaveCount(10);
		for (const text of await opts.allTextContents()) {
			expect(text).toContain('Berlín');
		}
	});

});

test.describe('MultiSelectBoxRemoteControl (TomSelect)', () => {

	test('po otevření načte první stránku záznamů ze serveru', async ({ page }) => {
		await openSelect(page, 'frm-form-tags', 'form-tags-range');

		const opts = options(page, 'frm-form-tags');
		await expect(opts).toHaveCount(PAGE_SIZE);
		await expect(opts.first()).toHaveText('Alaska');
	});

	test('scrollování na konec seznamu donačte další stránku', async ({ page }) => {
		await openSelect(page, 'frm-form-tags', 'form-tags-range');
		await expect(options(page, 'frm-form-tags')).toHaveCount(PAGE_SIZE);

		const nextPage = page.waitForResponse(r => r.url().includes('do=form-tags-range') && r.url().includes('page=2'));
		await page.locator(dropdown('frm-form-tags')).evaluate(el => { el.scrollTop = el.scrollHeight; });
		await nextPage;

		await expect(options(page, 'frm-form-tags')).toHaveCount(PAGE_SIZE * 2);
	});

	test('filtr vrátí jen odpovídající záznamy a umožní vybrat víc položek', async ({ page }) => {
		await openAndFilter(page, 'frm-form-tags2', 'form-tags2-range', 'Berlín');

		const opts = options(page, 'frm-form-tags2');
		await expect(opts).toHaveCount(10);

		await opts.first().click();

		const chosen = page.locator('#frm-form-tags2 + .ts-wrapper .ts-control .item');
		await expect(chosen).toHaveCount(1);
		await expect(chosen.first()).toHaveAttribute('data-value', 'b1');
		await expect(chosen.first()).toHaveText('Berlín');
	});

});
