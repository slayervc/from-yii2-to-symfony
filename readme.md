Это пример кода для моей статьи
"[Переход на Symfony в заскорузлом Yii2 монолите: подробный разбор](https://t.me/ak_architect/26)".
Далее следует оригинальный readme, который я писал для команды.

## Symfony console

Симфонийская консоль работает точно так же, как и в стандартном Symfony приложении, т. е. `php bin/console deb:cont`

## Команда update-index-php

Используйте команду `php bin/console update-index-php`, чтобы заменить старые файлы `index.php` на новые, выполняющие
запуск Symfony.

## Принцип совместной работы фреймворков Yii2 и Symfony

Общий алгоритм работы такой:

* Точкой входа в приложение является фреймворк Symfony
* **Абсолютно** все запросы обрабатываются фреймворком Symfony
* Если Symfony не находит в числе своих роутов запрашиваемый `path`, то запускается фреймворк Yii2.
* Результат работы Yii2 преобразуется в формат Symfony и отдается фреймворку Symfony так, будто никакого Yii2 и не было.


### Точка входа в приложение

Точки входа в приложения остались прежние: файлы `index.php`, лежащие в подкаталогах `/web` каталогов приложений.

Важно понимать, что для Symfony нетипично иметь больше одного файла `index.php`, и эта структура была сохранена
для совместимости с уже существующим кодом. В каждом файле `index.php` инициализируется переменная окружения
`YII_APP_NAME`, в которую записывается имя приложения Yii2 (biz, backend, api и т. д.) Это позволит нам
учитывать внутри фреймворка Symfony такую особенность Yii2, как выделение нескольких приложений.

В остальном файлы index.php работают как в Symfony: просто запускают `Kernel`.

### Роутинг для двух фреймворков

Класс `src\Common\Infrastructure\Symfony\EventListener\ApplicationSwitcher` подменяет собой стандартный
симфонийский роутер, выполняя две вещи:

1. Сначала пытаемся запустить стандартный симфонийский роутер
2. Если симфонийский роутер отрабатывает с ошибкой `NotFoundHttpException`, делается отметка о необходимости запустить Yii2

### Запуск Yii2

За запуск Yii2 отвечает `src\Common\Infrastructure\Symfony\EventListener\YiiApplicationRunner`. Этот класс
является обработчиком встроенного симфонийского события `RequestEvent` и отрабатывается самым последним. Если
в момент работы роутера была сделана пометка о необходимости запуска Yii2, то `YiiApplicationRunner` загружает
Yii2 приложение и передает обработку запроса в него. Дальше возможны два варианта:

1. Если запрос был обработан успешно, то полученный `Response` будет преобразован из формата Yii2 в формат Symfony с помощью `src\Common\Infrastructure\Yii2\Http\ResponseBridge`. Полученный `Response` будет передан в Symfony и выполнение приложения продолжится под управлением Symfony как обычно.
2. Если Yii2 выбросит `NotFoundHttpException`, то вместо него будет выброшена аналогичная ошибка Симфонийского роутера.

Иными словами, Symfony запускает внутри себя Yii2 приложение таким образом, чтобы два фреймворка никак не переплетались.
Yii2 ничего не знает о том, что он работает внутри Symfony и обрабатывает запрос как обычно. После обработки запроса
Yii2, результат обработки (как успешный, так и неуспешный) преобразуется в формат Symfony и отдается в `Kernel` так,
как будто никакого Yii2 и не было.

### Как устроен запуск Yii2

Для того, чтобы была возможность перехватить результат обработки запроса фреймворком Yii2 и передать его в Symfony,
Были подменены стандартные классы Yii2 `Application` и `Response` на `src\Common\Infrastructure\Yii2\Application` и
`src\Common\Infrastructure\Yii2\Http\SilentResponse` соответственно. Дело в том, что стандартное Yii2 приложение,
завершив обработку запроса, просто вываливало в поток вывода заголовки и содержимое запроса, в то время, как нам
необходимо передать заголовки и контент в Symfony и позволить симфонийскому приложению штатно завершить свою работу.

Ключевым классом в запуске Yii2 является `src\Common\Infrastructure\Yii2\ApplicationLoader`. Он выполняет следующие функции:
1. Выполняет предзагрузку Yii2, которая ранее выполнялась в файлах `index.php`
2. Следит, чтобы был запущен только один экземпляр Yii2.
3. Обеспечивает отложенный запуск Yii2 (см. ниже)

Отложенный запуск Yii2 необходим в связи с тем, что о необходимости запуска мы узнаем слишком рано: в момент, когда
симфонийский роутер не смог сопоставить `path` и контроллер. Дело в том, что в этот момент Symfony направляет работу
приложения совсем по другому пути, который исключает загрузку важных компонентов, таких как Security. Нам же необходимо
запускать Yii2 только после того, как все штатные симфонийские механизмы подготовки к обработке запроса отработают.
Именно поэтому мы разделяем во времени момент, когда мы уже знаем, что Yii2 должен быть запущен и собственно момент
запуска.

### Использование внутри Yii2 компонентов Symfony

Для того, чтобы получить внутри Yii2 доступ к какому-либо сервису Symfony (логгер, сериалайзер, валидатор и т. д.),
необходимо добавить соответсвующий сервис в класс `src\Common\Infrastructure\Yii2\Application`. В качестве примера можно
посмотреть, как используется `LoggerInterface`. Вам необходимо будет добавить приватное поле, геттер и расширить метод
`initSymfonyComponents()`

Далее, соответсвующую зависимость нужно добавить в `src\Common\Infrastructure\Yii2\ApplicationLoader` (добавить ее в
конструктор и, возможно, указать аргумент в файле `config/services/yii.yaml`).

После этого в классе `src\Common\Infrastructure\Yii2\ApplicationLoader` необходимо найти вызов `$this->app->initSymfonyComponents()`
и передать в него соответствующий сервис.

Далее, внутри Yii2 приложения (иными словами, в легаси коде) Вы можете получить доступ к симфонийскому сервису таким образом:

```php
$logger = \Yii::$app->getLogger();
```

### Использование компонетнов Yii2 внутри Symfony

Самый вероятный компонент Yii2, который вы можете захотеть использовать в приложении Symfony,
~~совершив тем самым великое зло~~, это ActiveRecord.

Необходимо стараться всеми силами избегать просачивание зависимостей из legacy Yii2 приложения в свежий симфонийский код.
Так, например, для того, чтобы не использовать ActiveRecord, достаточно написать новые классы доктриновских сущностей,
которые будут мапиться на старые таблицы БД.

Если вы все же решили ~~встать на путь великого греха~~ использовать компонент Yii2 в Symfony (например, в консольной команде),
то сделать это (лучше, конечно, не делать) можно так:

```php
#[AsCommand(name: 'test:ar')]
class TestAr extends Command
{
    public function __construct(
        private readonly ApplicationLoader $loader
    ) {
        parent::__construct(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Сначала обязательно необходимо загрузить Yii2 приложение. ApplicationLoader следит, чтобы загрузка
        //не выполнялась дважды, поэтому вы можете делать неограниченное количество вызовов load()
        $this->loader->load();

        //После загрузки можно использовать любые глобальные объекты Yii2, включая \Yii
        $category = CompanyNumber::findOne(['id' => 123]);
        $output->writeln($category->name);

        return 0;
    }
}
```

### Роутинг Symfony с учетом вида приложения Yii2

Yii2 выделяет несколько приложений: biz, backend, api и пр. Эта особенность была сохранена при переходе на Symfony.
Когда вы будете писать симфонийские контроллеры, вы наверняка захотите, чтобы роут к вашему контроллеру срабатывал только
для определнного приложения. Если вы ничего не предпримите, то ваш роут будет успешно обрабатываться на поддоменах
абсолютно всех приложений. Чтобы этого избежать, достаточно добавить в контроллер атрибут
`#[Route(condition: "service('app_kind_route_checker').check(request, 'app_name')")]`:

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route(condition: "service('app_kind_route_checker').check(request, 'biz')")]
class TestBizController extends AbstractController
{
    public function __construct(
        private readonly ApplicationLoader $applicationLoader,
    ) {
    }

    #[Route('/test', name: 'biz_test')]
    public function test(): Response
    {
        return new Response(
            '<html><body>Hi from BIZ!</body></html>'
        );
    }
}
```

Если вы хотите понять, как это работает, то ознакомьтесь с классом `src\Common\Infrastructure\Symfony\Routing\AppKindRouteChecker`
и разделом документации Symfony: https://symfony.com/doc/current/routing.html#matching-expressions

### Аутентификация в обоих фреймворках

При решении подавляющего большинства задач вам не придется думать о том, как это работает. Внутри Yii2 приложения (в легаси
коде) у вас по-прежнему доступен класс `common\models\user\User`, который вы можете получить например так: `\Yii::$app->user`.

Внутри Symfony приложения (когда вы пишете новый код) у вас доступен `src\Common\Infrastructure\Symfony\Security\User`,
который наполняется данными из той же таблицы, что и `common\models\user\User`. Вы можете получить залогиненного юзера
в любой точке симфонийского приложения стандартными средствами: через `Symfony\Bundle\SecurityBundle\Security::getUser`
или через `Symfony\Bundle\FrameworkBundle\Controller\AbstractController::getUser`.

Главное, что нужно понимать про аутентификацию в нашем приложении, так это то, что все запросы обрабатываются через
Symfony. Даже те, которые проходят через Yii2. Это значит, что аутентификация Symfony срабатывает **всегда**, а
аутентификация Yii2 срабатывает только тогда, когда происходит передача обработки запроса в Yii2.

Общий принцип работы такой:

* Сначала отрабатывает стандартная аутентификация Symfony с фаерволлами, access_control, юзер-провайдерами и пр. (см. документацию Symfony)
* Если возникает необходимость передать управление в Yii2, то в этот момент Symfony **принудительно** залогинит или разлогинит в Yii2 того пользователя, который был залогинен в Symfony. Это происходит в `YiiApplicationRunner`

Таким образом login и logout теперь **всегда** происходят средствами Symfony.

### Авторизация в обоих фреймворках

Внутри Yii2 ничего не поменялось, и вы по-прежнему можете пользоваться такими методами, как
`common\models\user\User::haveBizPermission()`.

Внутри Symfony легаси-юзер и его разрешения переводятся в формат Symfony и вы можете пользоваться стандартными
средствами Symfony следующим образом:

* Все bizPermissions доступны в Symfony в виде ролей с префиксом `ROLE_PERMISSION`, например `ROLE_PERMISSION_SERVICE_SCHEDULE_EDIT`
* `privilege` легаси-юзера также доступна с префиксом `ROLE`, например, `ROLE_OWNER`

Таким образом, вы можете писать следующий код:

```php
    #[Route('/one')]
    public function actionOne(): Response
    {
        //ROLE_PERMISSION_SERVICE_SCHEDULE_EDIT прочитана из таблицы permissions
        if (!$this->isGranted('ROLE_PERMISSION_SERVICE_SCHEDULE_EDIT')) {
            //запретить доступ
        }
    
        //...
    }

    #[Route('/two')]
    #[IsGranted('ROLE_OWNER')]
    public function actionTwo(): Response
    {
        //Вы можете пользоваться атрибутами Symfony (и я настоятельно рекомендую именно
        //этот способ). ROLE_OWNER - это `privilege`, взятая из таблицы `users`.
    }
```

Для того, чтобы симфонийская авторизация работала правильно, необходимо описать иерархию ролей
в разделе `role_hierarchy:` файла `config/packages/security.yaml`. Подробнее см. документацию:
https://symfony.com/doc/current/security.html#access-control-authorization

### Исключения для Symfony firewalls

Поскольку Symfony контролирует аутентификацию **всех** запросов, это может создавать проблемы.
Например, для приложения backend (админка) мы хотим, чтобы продолжала работать старая Yii2 аутентификация,
и чтобы Symfony никак не вмешивалась в этот процесс. Для этого в файле `config/packages/security.yaml`
фаерволлы сконфигурированы соответствующим образом. Вы можете ознакомиться с документацией Symfony,
чтобы понять, как именно они работают: https://symfony.com/doc/current/security.html#the-firewall

Тем не менее, в нашем приложении есть одна нестандартная настройка: это настройка роутов, для которых
необходимо отключить аутентификацию, т. е. сделать их публичными. Стандартный для Symfony раздел `access_control`
в файле `config/packages/security.yaml` мы вынуждены дополнять дефинициями класса `src\Common\Infrastructure\Symfony\Security\AppKindRequestMatcher`
в файле `config/services/security.yaml`. Это связано с необходимостью учитывать различные виды Yii2 приложений.
Работает это следующим образом: мы сначала указываем в `access_control`, что хотим использовать `request_matcher`:

```yaml
# config/packages/security.yaml

    access_control:
        - { request_matcher: access_control_biz_public_request_matcher, roles: [ PUBLIC_ACCESS ] }
```

Далее, мы объявляем сервис `access_control_biz_public_request_matcher` и указываем для него список путей, для которых
доступ будет открыт:

```yaml
# config/services/security.yaml

access_control_biz_public_request_matcher:
    class: src\Common\Infrastructure\Symfony\Security\AppKindRequestMatcher
    arguments:
        $currentAppKind: '%yii_app_name%'
        $requiredAppKind: 'biz'                   #Будет срабатывать только для приложения biz
        $paths:
            # Будет срабатывать только для следующих путей:
            - '^/user/signup'
            - '^/user/login'
            - '^/user/forgot-password'
            - '^/swagger'
            #...
```

В документации Symfony подробно описано, как работают request_matchers.

### Дополнительная информация

Вы можете самостоятельно изучить содержимое каталогов `src/Common/Infrastructure/Symfony` и `src/Common/Infrastructure/Yi2`,
чтобы познакомиться с другими нюансами совместной работы двух фреймворков.
