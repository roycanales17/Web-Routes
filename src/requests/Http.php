<?php

	namespace App\Routes\Requests;

	use App\Routes\Route;
	use App\Routes\Scheme\Buffer;
	use App\Routes\Scheme\Pal;
	use App\Routes\Scheme\Reflections;
	use App\Routes\Scheme\Validations;
	use Closure;

	abstract class Http
	{
		use Validations;
		use Reflections;

		protected string $uri = '';
		protected array $middlewares = [];
		protected string|array|Closure $actions = [];

		public function __construct(string $uri, mixed $actions)
		{
			$this->actions = $actions;
			$this->uri = $uri;
		}

		private function getActivePrefix(): array
		{
			$globalPrefix = Buffer::fetch('prefix') ?? [];
			$prefix = $this?->getPrefix() ?? [];

			return array_merge($globalPrefix, $prefix);
		}

		private function setupRouteMiddleware(): void
		{
			$middlewares = method_exists($this, 'GetMiddlewares') ? $this?->GetMiddlewares() : [];
			if ($globalMiddlewares = Buffer::fetch('middleware'))
				$middlewares = array_merge($globalMiddlewares, $middlewares);

			if ($middlewares) {
				$this->middlewares = $middlewares;
			}
		}

		private function setupRouteAction(): void
		{
			if (is_string($this->actions)) {
				$controller = method_exists($this, 'GetControllerName') ? $this->GetControllerName() : '';
				if ($controller) {
					$this->actions = [$controller, $this->actions];
				} else {
					if ($controllers = Buffer::fetch('controller')) {
						if ($controller = end($controllers)) {
							$this->actions = [$controller, $this->actions];
						}
					}
				}
			}
		}

		private function setupRouteName(array $prefix): void
		{
			$routeName = method_exists($this, 'getRouteName') ? $this->getRouteName() : '';
			if ($routeName)
				Pal::registerRouteName($routeName, $this->URISlashes($this->uri, $prefix));
		}

		private function capture(Closure $closure, int $code = 200, string $type = 'text/html'): void
		{
			ob_start(); $closure();
			Route::register(ob_get_clean(), $code, $type);
		}

		public function __destruct()
		{
			$this->setupRouteName($prefixes = $this->getActivePrefix());
			$this->setupRouteAction();
			$this->setupRouteMiddleware();

			if ($this->validateURI($this->uri, $prefixes, $params)) {

				if (!Pal::requestMethod(Pal::baseClassName(get_called_class())))
					return;

				if (!$this->validateMiddleware($this->middlewares)) {
					$this->capture(function () {
						echo(json_encode(['message' => 'Unauthorized']));
					}, 401, 'application/json');
					return;
				}

				$this->capture(function () {
					echo $this->performAction($this->actions, $params ?? []);
				});
			}
		}
	}