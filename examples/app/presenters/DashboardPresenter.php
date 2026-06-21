<?php declare(strict_types = 1);

/**
 * Copyright (c) since 2004 Martin Takáč (http://martin.takac.name)
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace App\Presenters;

use Nette\Application\UI\Presenter as BasePresenter;
use Nette\Application\UI\Form;
use Taco\Nette\Forms\CallbackQueryModel;


class DashboardPresenter extends BasePresenter
{

	protected function createComponentForm()
	{
		$form = $this->makeForm();

		$form->addText('title', 'Title a:')
			->setRequired('Please enter a title.');
		$form->addTextarea('content', 'Content:')
			->setRequired('Please enter a content.');

		$form->addSelectRemote('category', 'Category:', $this->getCategorySelectModel());
		$form->addSelectRemote('category2', 'Category 2:', $this->getCategorySelectModel())
			->setOption('description', 'S filtrováním - za využití js.')
			->controlPrototype->data('class', 'filterable');

		$form->addMultiSelectRemote('tags', 'Tags', $this->getCategorySelectModel());
		$form->addMultiSelectRemote('tags2', 'Tags 2', $this->getCategorySelectModel())
			->setOption('description', 'S filtrováním - za využití js.')
			->controlPrototype->data('class', 'filterable');

		$form->addSelectRemote('countries', 'Country:', $this->getCountrySelectModel())
			->setOption('description', 'Dekorovaná položka: vlaječka + název státu.')
			->controlPrototype->data('class', 'filterable');

		$form->addSelectRemote('accounts', 'Account:', $this->getAccountSelectModel())
			->setOption('description', 'Dekorovaná položka: ikona vlevo, label a popisek pod sebou.')
			->controlPrototype->data('class', 'filterable');

		$form->setCurrentGroup(NULL);
		$form->addSubmit('submit', 'Save')
			->setAttribute('class', 'default');

		$form->addSubmit('cancel', 'Cancel')
			->setValidationScope([]);

		$form->onSuccess[] = static function($form, $values): void {
			dump($values);
		};

		return $form;
	}



	private function makeForm(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->onSuccess[] = $this->createProcessSubmitted();
		$form->onAnchor[] = static function($form): void {
			if ( ! $form->isSubmitted()) {
				// Initialize default values here if needed
			}
		};
		return $form;
	}



	private function createProcessSubmitted()
	{
		return function (Form $form): void {

			if ($this->presenter->isAjax()) {
				$this->presenter->redrawControl('task');
				//~ $form->redrawControl();
				$this->presenter->redrawControl($form->getName());
			}

			if (isset($form['cancel']) && $form['cancel']->isSubmittedBy()) {
				$this->presenter->redirect('Dashboard:');
			}
		};
	}



	private function getCategorySelectModel(): CallbackQueryModel
	{
		$data = self::getData();

		// CallbackQueryModel is buildin implementation of generic QueryModel.
		return new CallbackQueryModel(static function($term, $page, $pageSize) use ($data) {
			$results = [];
			foreach ($data as $x) {
				if ($term && stripos((string) $x->label, $term) === False) {
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
		}, static function($id) use ($data): ?array {
			foreach ($data as $x) {
				if ($x->id === $id) {
					return (array) $x;
				}
			}
			return NULL;
		});
	}



	private function getCountrySelectModel()
	{
		$data = self::getCountryData();

		// Items carry an extra "flag" field on top of id/label, picked up by the JS renderer.
		return new CallbackQueryModel(static function($term, $page, $pageSize) use ($data) {
			$results = [];
			foreach ($data as $x) {
				if ($term && stripos($x->label, $term) === False) {
					continue;
				}
				$results[] = (object) [
					'id' => $x->id,
					'label' => $x->label,
					'flag' => $x->flag,
				];
			}
			$total = count($results);
			$offset = ($page - 1) * $pageSize;
			return (object) [
				'total' => $total,
				'items' => array_slice($results, $offset, $pageSize),
			];
		}, static function($id) use ($data) {
			foreach ($data as $x) {
				if ($x->id === $id) {
					return (array) $x;
				}
			}
			return NULL;
		});
	}



	private function getAccountSelectModel()
	{
		$data = self::getAccountData();

		// Items carry extra "icon" and "description" fields on top of id/label.
		return new CallbackQueryModel(static function($term, $page, $pageSize) use ($data) {
			$results = [];
			foreach ($data as $x) {
				if ($term && stripos($x->label, $term) === False) {
					continue;
				}
				$results[] = (object) [
					'id' => $x->id,
					'label' => $x->label,
					'icon' => $x->icon,
					'description' => $x->description,
				];
			}
			$total = count($results);
			$offset = ($page - 1) * $pageSize;
			return (object) [
				'total' => $total,
				'items' => array_slice($results, $offset, $pageSize),
			];
		}, static function($id) use ($data) {
			foreach ($data as $x) {
				if ($x->id === $id) {
					return (array) $x;
				}
			}
			return NULL;
		});
	}



	private static function getCountryData()
	{
		return [
			(object)['id' => 'cz', 'label' => 'Česká republika', 'flag' => '🇨🇿'],
			(object)['id' => 'sk', 'label' => 'Slovensko', 'flag' => '🇸🇰'],
			(object)['id' => 'de', 'label' => 'Německo', 'flag' => '🇩🇪'],
			(object)['id' => 'at', 'label' => 'Rakousko', 'flag' => '🇦🇹'],
			(object)['id' => 'pl', 'label' => 'Polsko', 'flag' => '🇵🇱'],
			(object)['id' => 'fr', 'label' => 'Francie', 'flag' => '🇫🇷'],
			(object)['id' => 'es', 'label' => 'Španělsko', 'flag' => '🇪🇸'],
			(object)['id' => 'it', 'label' => 'Itálie', 'flag' => '🇮🇹'],
			(object)['id' => 'pt', 'label' => 'Portugalsko', 'flag' => '🇵🇹'],
			(object)['id' => 'nl', 'label' => 'Nizozemsko', 'flag' => '🇳🇱'],
			(object)['id' => 'be', 'label' => 'Belgie', 'flag' => '🇧🇪'],
			(object)['id' => 'ch', 'label' => 'Švýcarsko', 'flag' => '🇨🇭'],
			(object)['id' => 'se', 'label' => 'Švédsko', 'flag' => '🇸🇪'],
			(object)['id' => 'no', 'label' => 'Norsko', 'flag' => '🇳🇴'],
			(object)['id' => 'dk', 'label' => 'Dánsko', 'flag' => '🇩🇰'],
			(object)['id' => 'fi', 'label' => 'Finsko', 'flag' => '🇫🇮'],
			(object)['id' => 'us', 'label' => 'Spojené státy', 'flag' => '🇺🇸'],
			(object)['id' => 'gb', 'label' => 'Velká Británie', 'flag' => '🇬🇧'],
			(object)['id' => 'jp', 'label' => 'Japonsko', 'flag' => '🇯🇵'],
			(object)['id' => 'cn', 'label' => 'Čína', 'flag' => '🇨🇳'],
		];
	}



	private static function getAccountData()
	{
		return [
			(object)['id' => 'u1', 'label' => 'Martin Takáč', 'icon' => '👤', 'description' => 'Administrátor'],
			(object)['id' => 'u2', 'label' => 'Jana Nováková', 'icon' => '👩', 'description' => 'Účetní'],
			(object)['id' => 'u3', 'label' => 'Petr Svoboda', 'icon' => '👨', 'description' => 'Obchodní zástupce'],
			(object)['id' => 'u4', 'label' => 'Lucie Veselá', 'icon' => '👩‍💼', 'description' => 'Marketing'],
			(object)['id' => 'u5', 'label' => 'Tomáš Dvořák', 'icon' => '👨‍💻', 'description' => 'Vývojář'],
			(object)['id' => 'u6', 'label' => 'Eva Procházková', 'icon' => '👩‍💻', 'description' => 'Vývojářka'],
			(object)['id' => 'u7', 'label' => 'Jan Černý', 'icon' => '👨‍🔧', 'description' => 'Podpora'],
			(object)['id' => 'u8', 'label' => 'Marie Horáková', 'icon' => '👩‍🔧', 'description' => 'Podpora'],
			(object)['id' => 'u9', 'label' => 'David Kovář', 'icon' => '👨‍💼', 'description' => 'Obchodní zástupce'],
			(object)['id' => 'u10', 'label' => 'Klára Pokorná', 'icon' => '👩‍🎓', 'description' => 'HR'],
			(object)['id' => 'u11', 'label' => 'Filip Marek', 'icon' => '👨‍🎓', 'description' => 'HR'],
			(object)['id' => 'u12', 'label' => 'Tereza Bartošová', 'icon' => '👩‍⚖️', 'description' => 'Právní oddělení'],
		];
	}



	/**
	 * @return array<\stdClass>
	 */
	private static function getData()
	{
		return [
			(object)['id' => 'a1', 'label' => 'Alaska'],
			(object)['id' => 'b1', 'label' => 'Berlín'],
			(object)['id' => 'c1', 'label' => 'Chikaco'],
			(object)['id' => 'd1', 'label' => 'Denver'],
			(object)['id' => 'e1', 'label' => 'Edmond'],
			(object)['id' => 'a2', 'label' => 'Alaska Beta'],
			(object)['id' => 'b2', 'label' => 'Berlín Beta'],
			(object)['id' => 'c2', 'label' => 'Chikaco Beta'],
			(object)['id' => 'd2', 'label' => 'Denver Beta'],
			(object)['id' => 'e2', 'label' => 'Edmond Beta'],
			(object)['id' => 'f2', 'label' => 'Alaska Gama'],
			(object)['id' => 'g2', 'label' => 'Berlín Gama'],
			(object)['id' => 'h2', 'label' => 'Chikaco Gama'],
			(object)['id' => 'i2', 'label' => 'Edmond Gama'],
			(object)['id' => 'j2', 'label' => 'Alaska Delta'],
			(object)['id' => 'k2', 'label' => 'Berlín Delta'],
			(object)['id' => 'l2', 'label' => 'Chikaco Delta'],
			(object)['id' => 'm2', 'label' => 'Denver Delta'],
			(object)['id' => 'n2', 'label' => 'Edmond Delta'],
			(object)['id' => 'o2', 'label' => 'Alaska Omega'],
			(object)['id' => 'p2', 'label' => 'Berlín Omega'],
			(object)['id' => 'q2', 'label' => 'Chikaco Omega'],
			(object)['id' => 'r2', 'label' => 'Denver Omega'],
			(object)['id' => 's2', 'label' => 'Edmond Omega'],
			(object)['id' => 't2', 'label' => 'Alaska Theta'],
			(object)['id' => 'u2', 'label' => 'Berlín Theta'],
			(object)['id' => 'w2', 'label' => 'Chikaco Theta'],
			(object)['id' => 'x2', 'label' => 'Denver Theta'],
			(object)['id' => 'z2', 'label' => 'Edmond Theta'],
			(object)['id' => 'a3', 'label' => 'Dog Alaska'],
			(object)['id' => 'b3', 'label' => 'Dog Berlín'],
			(object)['id' => 'c3', 'label' => 'Dog Chikaco'],
			(object)['id' => 'd3', 'label' => 'Dog Denver'],
			(object)['id' => 'e3', 'label' => 'Dog Edmond'],
			(object)['id' => 'h3', 'label' => 'Chikaco Alfa'],
			(object)['id' => 'i3', 'label' => 'Edmond Alfa'],
			(object)['id' => 'j3', 'label' => 'Alaska Alfa'],
			(object)['id' => 'k3', 'label' => 'Berlín Alfa 3'],
			(object)['id' => 'l3', 'label' => 'Chikaco Alfa 3'],
			(object)['id' => 'm3', 'label' => 'Denver Delta 3'],
			(object)['id' => 'n3', 'label' => 'Edmond Delta 3'],
			(object)['id' => 'o3', 'label' => 'Alaska Omega 3'],
			(object)['id' => 'p3', 'label' => 'Berlín Omega 3'],
			(object)['id' => 'q3', 'label' => 'Chikaco Omega 3'],
			(object)['id' => 'r3', 'label' => 'Denver Omega 3'],
			(object)['id' => 's3', 'label' => 'Edmond Omega 3'],
			(object)['id' => 't3', 'label' => 'Alaska Theta 3'],
			(object)['id' => 'u3', 'label' => 'Berlín Theta 3'],
			(object)['id' => 'w3', 'label' => 'Chikaco Theta 3'],
			(object)['id' => 'x3', 'label' => 'Denver Theta 3'],
			(object)['id' => 'z3', 'label' => 'Edmond Theta 3'],
		];
	}

}
