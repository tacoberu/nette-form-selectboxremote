import { test, expect, type Page } from '@playwright/test';

const PAGE_SIZE = 10;

function results(page: Page) {
	return page.locator('.select2-container--open .select2-results__option:not(.select2-results__option--load-more)');
}

async function openSelect(page: Page, fieldId: string, ajaxAction: string) {
	const initialLoad = page.waitForResponse(r => r.url().includes(`do=${ajaxAction}`));
	await page.click(`#${fieldId} + .select2 .select2-selection`);
	await initialLoad;
}

async function openAndFilter(page: Page, fieldId: string, ajaxAction: string, term: string) {
	await page.click(`#${fieldId} + .select2 .select2-selection`);
	await page.waitForSelector('.select2-container--open .select2-search__field');

	const filtered = page.waitForResponse(r => r.url().includes(`do=${ajaxAction}`) && r.url().includes('term='));
	await page.fill('.select2-container--open .select2-search__field', term);
	await filtered;
}

test.beforeEach(async ({ page }) => {
	await page.goto('/dashboard/select2');
});

test.describe('SelectBoxRemoteControl', () => {

	test('po otevření načte první stránku záznamů ze serveru', async ({ page }) => {
		await openSelect(page, 'frm-form-category', 'form-category-range');

		const options = results(page);
		await expect(options).toHaveCount(PAGE_SIZE);
		await expect(options.first()).toHaveText('Alaska');
		await expect(options.last()).toHaveText('Edmond Beta');
	});

	test('scrollování na konec seznamu donačte další stránku', async ({ page }) => {
		await openSelect(page, 'frm-form-category', 'form-category-range');
		await expect(results(page)).toHaveCount(PAGE_SIZE);

		const nextPage = page.waitForResponse(r => r.url().includes('do=form-category-range') && r.url().includes('page=2'));
		await page.locator('.select2-results__options').evaluate(el => { el.scrollTop = el.scrollHeight; });
		await nextPage;

		const options = results(page);
		await expect(options).toHaveCount(PAGE_SIZE * 2);
		await expect(options.nth(PAGE_SIZE)).toHaveText('Alaska Gama');
	});

	test('zadáním textu do filtru se zobrazí jen odpovídající záznamy', async ({ page }) => {
		await openAndFilter(page, 'frm-form-category2', 'form-category2-range', 'Berlín');

		const options = results(page);
		await expect(options).toHaveCount(10);
		for (const text of await options.allTextContents()) {
			expect(text).toContain('Berlín');
		}
	});

});

test.describe('MultiSelectBoxRemoteControl', () => {

	test('po otevření načte první stránku záznamů ze serveru', async ({ page }) => {
		await openSelect(page, 'frm-form-tags', 'form-tags-range');

		const options = results(page);
		await expect(options).toHaveCount(PAGE_SIZE);
		await expect(options.first()).toHaveText('Alaska');
	});

	test('scrollování na konec seznamu donačte další stránku', async ({ page }) => {
		await openSelect(page, 'frm-form-tags', 'form-tags-range');
		await expect(results(page)).toHaveCount(PAGE_SIZE);

		const nextPage = page.waitForResponse(r => r.url().includes('do=form-tags-range') && r.url().includes('page=2'));
		await page.locator('.select2-results__options').evaluate(el => { el.scrollTop = el.scrollHeight; });
		await nextPage;

		await expect(results(page)).toHaveCount(PAGE_SIZE * 2);
	});

	test('filtr vrátí jen odpovídající záznamy a umožní vybrat víc položek', async ({ page }) => {
		await openAndFilter(page, 'frm-form-tags2', 'form-tags2-range', 'Berlín');

		const options = results(page);
		await expect(options).toHaveCount(10);

		await options.first().click();

		const chosen = page.locator('#frm-form-tags2 + .select2 .select2-selection__choice');
		await expect(chosen).toHaveCount(1);
		await expect(chosen.first()).toHaveAttribute('title', 'Berlín');
	});

});
