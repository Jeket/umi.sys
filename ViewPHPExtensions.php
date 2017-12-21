<?php
	/**
	 * Системное расширение PHP шаблонизатора.
	 */
	class ViewPhpExtension implements IPhpExtension {

		/** @var array $systemModules список системных модулей */
		private $systemModules = ['core', 'system', 'custom'];

		/** @var array $cacheLabels кэш меток локализации */
		private $cacheLabels = [];

		/** @var array $commonVars хранит массив общих для шаблона переменных */
		private $commonVars = [];

		/** @var umiTemplaterPHP PHP шаблонизатор */
		private $umiTemplaterPHP;

		/**
		 * Конструктор
		 * @param umiTemplaterPHP $templater PHP шаблонизатор
		 */
		public function __construct(umiTemplaterPHP $templater) {
			$this->umiTemplaterPHP = $templater;
		}

		/**
		 * Возвращает общие для шаблонов переменные.
		 * @return array
		 */
		public function getCommonVars() {
			return $this->commonVars;
		}

		/**
		 * Устанавливает общие для шаблонов переменные.
		 * @param string $name имя устанавливаемой переменной
		 * @param array $commonVars
		 */
		public function setCommonVars($name, $commonVars) {
			$this->commonVars[$name] = $commonVars;
		}

		/**
		 * Возвращает перевод метки.
		 * @param string $label метка
		 * @return string
		 */
		public function getCacheLabel($label) {
			return $this->cacheLabels[$label];
		}

		/**
		 * Устанавливает список меток.
		 * @param array $cacheLabels
		 */
		public function setCacheLabels(array $cacheLabels) {
			$this->cacheLabels = $cacheLabels;
		}

		/**
		 * Проверяет существование метки перевода.
		 * @param string $label метка
		 * @return bool
		 */
		public function isSetLabel($label) {
			return isset($this->cacheLabels[$label]);
		}

		/**
		 * Проверяет не пустой ли массив с метками.
		 * @return bool
		 */
		public function isNotEmptyCacheLabels() {
			return empty($this->cacheLabels);
		}

		/**
		 * Возвращает список системных модулей.
		 * @return array
		 */
		public function getSystemModules() {
			return $this->systemModules;
		}

		/**
		 * {@inheritdoc}
		 */
		public function getName() {
			return __CLASS__;
		}

		/**
		 * Возвращает каноническую ссылку для страницы
		 * @param array $variables глобальные переменные текущей страницы
		 * @return string
		 */
		public function getCanonicalLinkTag(array $variables) {
			if (!isset($variables['pageId'])) {
				return '';
			}

			$result = $this->umiTemplaterPHP->macros('seo', 'getRelCanonical', ['default', $variables['pageId']]);

			if (!isset($result['link']) || !$result['link']) {
				return '';
			}

			return '<link rel="canonical" href="' . $result['link'] . '" />';
		}

		/**
		 * Возвращает относительный (от корневой директории, доступной в WEB) путь до директории с ресурсами шаблона
		 * @param string $workingDir корневая директория, относительно которой строится путь
		 * @return string
		 */
		public function getResourceDirectory($workingDir = CURRENT_WORKING_DIR) {
			$resourceDirAbsolutePath = cmsController::getInstance()
				->getResourcesDirectory();
			return $this->removeSubPathAtStart($workingDir, $resourceDirAbsolutePath);
		}

		/**
		 * Выполняет xpath запрос к внутреннему результату работы текущего метода
		 * @param array $variables переменные текущего вызова
		 * @param string $query xpath запрос
		 * @return DOMNodeList
		 * @throws RuntimeException
		 */
		public function xpathToInnerResult(array $variables, $query) {
			if (!is_string($query) || strlen($query) == 0) {
				throw new RuntimeException('Wrong xpath query given');
			}

			if (!isset($variables['xml:data'])) {
				throw new RuntimeException('Current method don\'t save any xml data');
			}

			$document = new DOMDocument();

			if (!$document->loadXML($variables['xml:data'])) {
				throw new RuntimeException('Current method create wrong xml data');
			}

			$xpath = new DOMXPath($document);
			/**
			 * @var DOMNodeList $nodes
			 */
			return $xpath->evaluate($query);
		}

		/**
		 * Вызывает макрос и возвращает результат его выполнения
		 * @param string $moduleName модуль макроса
		 * @param string $method метод макроса
		 * @param array $arguments аргументы вызова
		 * @param array $extProps дополнительные поля, которые требуется получить в результате
		 * @param array $extGroups дополнительные группы полей, которые требуется получить в результате
		 * @return mixed
		 * @throws RuntimeException
		 * @throws Exception
		 */
		public function macros($moduleName, $method, $arguments = [], $extProps = [], $extGroups = []) {
			$umiConfig = mainConfiguration::getInstance();

			try {
				if (!isset($moduleName)) {
					throw new RuntimeException(__METHOD__ . ": не передано название модуля");
				}

				if (!isset($method)) {
					throw new RuntimeException(__METHOD__ . ": не передано название макроса");
				}

				if (!is_array($arguments)) {
					throw new RuntimeException(__METHOD__ . ": не переданы аргументы для макроса");
				}

				$isAllowed = true;

				if (in_array($moduleName, $this->getSystemModules())) {
					$module = system_buildin_load($moduleName);
				} else {
					$module = cmsController::getInstance()->getModule($moduleName);
					$isAllowed = (bool) system_is_allowed($moduleName, $method);
				}

				if (!$module) {
					throw new RuntimeException(__METHOD__ . ": не удалось загрузить модуль {$moduleName}");
				}

				if (!$isAllowed) {
					return null;
				}

				$key = '';
				$cacheEnabled = (bool) $umiConfig->get('cache', 'streams.cache-enabled');
				$cacheLifeTime = (int) $umiConfig->get('cache', 'streams.cache-lifetime');
				$cacheAllowed = $cacheEnabled && $cacheLifeTime > 0;
				$cacheFrontend = cacheFrontend::getInstance();

				if ($cacheAllowed) {
					$key = $moduleName . '/' . $method . http_build_query(array_merge($arguments, $extProps, $extGroups)) . $cacheFrontend->getQueryStringHash();
					$result = $cacheFrontend->loadData($key);

					if ($result) {
						return $result;
					}
				}

				$previousExtProps = def_module::getMacrosExtendedProps();
				$previousExtGroups = def_module::getMacrosExtendedGroups();
				def_module::setMacrosExtendedResult($extProps, $extGroups);
				$result = call_user_func_array([$module, $method], $arguments);
				$cleanResult = $this->getTemplateEngine()
					->cleanData($result);

				def_module::setMacrosExtendedResult($previousExtProps, $previousExtGroups);

				if ($cacheAllowed) {
					$cacheFrontend->saveData($key, $cleanResult, $cacheLifeTime);
				}

				return $cleanResult;

			} catch (Exception $e) {
				if (! (bool) $umiConfig->get('system', 'suppress-exceptions-in-php-macros')) {
					throw $e;
				}
			}
		}

		/**
		 * Возвращает экземпляр страницы по ее адресу
		 * @param string $path адрес (ссылка) страницы
		 * @return iUmiHierarchyElement|bool
		 */
		public function getPageByPath($path) {
			return umiHierarchy::getInstance()->getElement(
				umiHierarchy::getInstance()->getIdByPath($path)
			);
		}

		/**
		 * Возвращает экземпляр страницы по ее id
		 * @param int $id идентификатор страницы
		 * @return iUmiHierarchyElement|bool
		 */
		public function getPageById($id) {
			return umiHierarchy::getInstance()
				->getElement($id);
		}

		/**
		 * Возвращает объект по id
		 * @param int $id идентификатор объекта
		 * @return iUmiObject|bool
		 */
		public function getObjectById($id) {
			return umiObjectsCollection::getInstance()
				->getObject($id);
		}

		/**
		 * Возвращает адрес (ссылку) страницы
		 * @param iUmiHierarchyElement $page страница
		 * @return string
		 */
		public function getPath(iUmiHierarchyElement $page) {
			return umiHierarchy::getInstance()->getPathById($page->getId());
		}

		/**
		 * Возвращает страницу по умолчанию
		 * @return iUmiHierarchyElement|false
		 */
		public function getDefaultPage() {
			return umiHierarchy::getInstance()->getDefaultElement();
		}

		/**
		 * Возвращает перевод метки.
		 * @param string $label метка
		 * @param bool|string $path @see ulangStream::getLabel()
		 * @return string
		 */
		public function translate($label, $path = false) {
			$cmsController = cmsController::getInstance();

			if ($this->isNotEmptyCacheLabels()) {
				$fileI18N = $cmsController->getTemplatesDirectory() . 'i18n/i18n.' . $cmsController->getCurrentLang()->getPrefix() . '.php';
				if (file_exists($fileI18N)) {
					$this->setCacheLabels(require $fileI18N);
					if ($this->isSetLabel($label)) {
						return $this->getCacheLabel($label);
					}
				}
			} else {
				if ($this->isSetLabel($label)) {
					return $this->getCacheLabel($label);
				}
			}

			return getLabel($label, $path);
		}

		/**
		 * Возвращает обработанный Request-параметр.
		 * @param string $name имя параметра
		 * @param mixed $default значений по умолчанию, если параметр не объявлен
		 * @return mixed
		 */
		public function getRequest($name, $default = null) {
			$param = getRequest($name);

			if (!$param) {
				return $default;
			}

			return htmlspecialchars($param);
		}

		/**
		 * Возвращает Request-параметр.
		 * @param string $name имя параметра
		 * @param mixed $default значений по умолчанию, если параметр не объявлен
		 * @return mixed
		 */
		public function getRawRequest($name, $default = null) {
			$param = getRequest($name);

			if (!$param) {
				return $default;
			}

			return $param;
		}

		/**
		 * Возвращает значение запрошенной общей переменной.
		 * @param string $name имя переменной
		 * @return mixed
		 */
		public function getCommonVar($name) {
			$commonVars = $this->getCommonVars();
			return isset($commonVars[$name]) ? $commonVars[$name] : null;
		}

		/**
		 * Устанавливает значение общей переменной.
		 * @param string $name имя переменной
		 * @param mixed $value значение переменной
		 */
		public function setCommonVar($name, $value) {
			$this->setCommonVars($name, $value);
		}

		/**
		 * Проверяет существование общей переменной.
		 * @param string $name имя переменной
		 * @return bool
		 */
		public function isSetCommonVar($name) {
			$commonVars = $this->getCommonVars();
			return isset($commonVars[$name]);
		}

		/**
		 * Применяет tpl шаблонизатор к строковым данным
		 * @param string $value шаблонизируемые данные
		 * @param int|bool $elementId идентификатор страницы, к которой имеют отношения данные
		 * @param int|bool $objectId идентификатор объекта, к которой имеют отношения данные
		 * @return mixed
		 */
		public function parseTplMacros($value, $elementId = false, $objectId = false) {
			if (!is_string($value)) {
				return $value;
			}

			if (strpos($value, '%') === false) {
				return $value;
			}

			$tplTemplate = umiTemplater::create('TPL');
			$tplTemplate->setScope($elementId, $objectId);
			return $tplTemplate->parse([], $value);
		}

		/**
		 * Выполняет usel запрос.
		 * @see uselStream::call()
		 * @param string $uselName название шаблона usel
		 * @param array|null $params параметры вызова
		 * @return array
		 */
		public function usel($uselName, $params = null) {
			$stream = new uselStream;
			return $stream->call($uselName, $params);
		}

		/**
		 * Возвращает PHP шаблонизатор
		 * @return umiTemplaterPHP
		 */
		protected function getTemplateEngine() {
			return $this->umiTemplaterPHP;
		}

		/**
		 * Удаляет часть пути из его начала
		 * @param string $needle удаляемая часть пути
		 * @param string $haystack исходный путь
		 * @return string путь, полученный в результате удаления его части
		 */
		private function removeSubPathAtStart($needle, $haystack) {
			$hasSubPathInStart = ( strpos($haystack, $needle) === 0 );

			if ($hasSubPathInStart) {
				return str_replace($needle, '', $haystack);
			}

			return $haystack;
		}
	}
