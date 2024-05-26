Nette SelectboxRemote
=====================

Select, který načítá data vzdáleně. Je k dispozici pro velké množství záznamů, které je potřeba filtrovat a načítat postupně po blocích. Balíček nabízí instantní řešení pro javascriptovou obsluhu Select2, nebo TomSelect.

Tak jako v obyčejném selectboxu se předává pole hodnot, zde se předává model poskytující hodnoty.
Jako model může posloužit univerzální `CallbackQueryModel`, který vše vyřeší pomocí callbacků.
Balíček zároveň umožňuje vytvořit si vlastní implementaci na míru.

Veškerá komunikace s backendem probíhá stejným systematickým způsobem přes tento model — není
tedy potřeba vytvářet žádný speciální AJAX endpoint ani presenter akci. Načítání a filtrování dat
řeší ovládací prvek interně přes vlastní signál, stejně jako u jiných signálů v Nette komponentách.
Přidání `addSelectRemote()` je tak z pohledu použití naprosto stejné jako přidání běžného,
ne-remote selectboxu — jediný rozdíl je, že se místo pole hodnot předává model.


## Instalace

```
composer require tacoberu/nette-form-selectboxremote
```


## Použití

### Registrace extension

Po registraci extension jsou v kontejneru k dispozici zkratky `addSelectRemote()` a `addMultiSelectRemote()`:

```
extensions:
	- Taco\Nette\Forms\Controls\SelectBoxRemoteExtension
```


### Javascript

Balíček nabízí dvě hotové obsluhy, obě napsané v TypeScriptu a zkompilované do JS modulu, který je
součástí balíčku (`vendor/tacoberu/nette-form-selectboxremote/assets/`). Soubor je k dispozici i
přímo v repozitáři a je třeba ho zpřístupnit z veřejné složky (symlink nebo kopie) a naimportovat
jako ES modul. Filtrování konkrétního pole je k dispozici po zapnutí atributem
`data-class="filterable"` na straně PHP:

```php
$form->addSelectRemote('category2', 'Category 2:', $model)
	->controlPrototype->data('class', 'filterable');
```

#### Select2

`assets/remoteselect2.ts` → `assets/remoteselect2.js`, k dispozici je export `initSelect2Impl(el, overrides?)`.

```html
	<link href="https://cdn.jsdelivr.net/npm/select2@4.0.10/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.0.10/dist/js/select2.min.js"></script>

	<script type="module">
		import { initSelect2Impl } from "/js/remoteselect2.js";
		document.querySelectorAll('select')
			.forEach(initSelect2Impl);
	</script>
```

jQuery je potřeba pouze jako závislost samotného Select2 (ten je k dispozici pouze jako jQuery
plugin) — funkce `initSelect2Impl` sama o sobě na jQuery nezávisí, atributy čte přes nativní
`element.dataset`. Stránkování je k dispozici díky vestavěné Select2 mechanice `ajax`/`pagination.more`.

#### TomSelect

`assets/TomSelectAdapter.ts` → `assets/TomSelectAdapter.js`, k dispozici je export `initTomSelectImpl(el, overrides?)`.
Na rozdíl od Select2 je TomSelect k dispozici jako čistě vanilla JS řešení, bez závislosti na jQuery.

```html
	<link href="https://cdn.jsdelivr.net/npm/tom-select@2.6.1/dist/css/tom-select.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/tom-select@2.6.1/dist/js/tom-select.complete.min.js"></script>

	<script type="module">
		import { initTomSelectImpl } from "/js/TomSelectAdapter.js";
		document.querySelectorAll('select')
			.forEach(initTomSelectImpl);
	</script>
```

Stránkování je k dispozici díky oficiálnímu pluginu `virtual_scroll` (je součástí
`tom-select.complete.min.js`) a zapíná se automaticky pro pole s `data-type="remoteselect"`.

#### Další konfigurace (overrides)

Obě funkce, `initSelect2Impl` i `initTomSelectImpl`, nabízí druhý nepovinný parametr — objekt,
který se sloučí s automaticky odvozeným nastavením. Díky tomu je k dispozici libovolná další
funkce dané knihovny, například řazení prvků u multiselectu (plugin `drag_drop` u TomSelect)
nebo možnost přidat nový záznam (`tags: true` u Select2, `create: true` u TomSelect):

```js
document.querySelectorAll('select').forEach((el) => {
	const overrides = el.multiple ? { plugins: ['drag_drop'], create: true } : {};
	initTomSelectImpl(el, overrides);
});
```

U TomSelect jsou `plugins` z overrides slučovány s `virtual_scroll`, který nelze vypnout — bez
něj by stránkování přes AJAX nebylo k dispozici.



### Použití v PHP

```php
$form = new Nette\Forms\Form;

// CallbackQueryModel is buildin implementation of generic QueryModel.
$categorySelectQueryModel = new CallbackQueryModel(function($term, $page, $pageSize) use ($data) {
	$results = [];
	foreach ($data as $x) {
		if ($term && stripos($x->label, $term) === False) {
			continue;
		}
		$results[] = (object) [
			'id' => $x->id,
			'label' => $x->label,
		];
	}
	$total = count($results);
	$offset = ($page - 1) * $pageSize;
	return (object) [
		'total' => $total,
		'items' => array_slice($results, $offset, $pageSize),
	];
}, function($id) use ($data) {
	foreach ($data as $x) {
		if ($x->id === $id) {
			return (array) $x;
		}
	}
	return NULL;
});

// ...

$form->addSelectRemote('category', 'Category:', $categorySelectQueryModel);
$form->addMultiSelectRemote('tags', 'Tags:', $tagsSelectQueryModel);
```

![Výsledek pro Select2 vypadá takto](docs/select2.png)

![Výsledek pro TomSelect vypadá takto](docs/tom-select.png)



## Příklad aplikace

V `examples/` je k dispozici kompletní ukázková Nette aplikace se dvěma stránkami —
`/dashboard/select2` a `/dashboard/tomselect` — demonstrující stejný PHP formulář obsluhovaný
oběma JS knihovnami.

```bash
composer install
cp .env-example .env
php -S localhost:8001 examples/document_root/router.php
```

Aplikace je pak k dispozici na adrese z `.env` (`APP_URL`).



## Sestavení assets (TypeScript)

```bash
npm run build:assets
```

Příkaz zkompiluje všechny `*.ts` v `assets/` (aktuálně `remoteselect2.ts` a `TomSelectAdapter.ts`)
do sousedních `.js` souborů.



## E2E testy (Playwright)

```bash
npm install
npx playwright install chromium  # jen poprvé

npm run test:e2e        # spustit testy
npm run test:e2e:ui     # interaktivní UI
```

K dispozici jsou dvě sady e2e testů: `tests/e2e/remote-select.spec.ts` pro variantu se Select2
a `remote-select-tomselect.spec.ts` pro stejné scénáře (načtení, donačtení stránky, filtrování)
s TomSelect. Adresa testované aplikace se nastavuje v `.env` přes `APP_URL`, výstupy se ukládají
do `temp/`.
