<?php

	namespace App\Routes\Configurations\Builder;

	use App\Routes\Configurations\Blueprints\Name as BaseName;
	use App\Routes\Configurations\Blueprints\Controller;
	use App\Routes\Configurations\Blueprints\Group;
	use App\Routes\Configurations\Blueprints\Middleware;
	use App\Routes\Configurations\Blueprints\Prefix;
	use App\Routes\Configurations\Config;

	class Name extends Config
	{
		private string $nameProperty;

		use BaseName {
			RegisterName as private name;
		}
		use Middleware {
			RegisterMiddleware as public middleware;
		}
		use Group {
			RegisterGroup as public group;
		}
		use Prefix {
			RegisterPrefix as public prefix;
		}
		use Controller {
			RegisterController as public controller;
		}

		function __construct(string $name)
		{
			$this->nameProperty = $name;
		}

		protected function register(): void
		{
			$this->RegisterName($this->nameProperty);
		}
	}