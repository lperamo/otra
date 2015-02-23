<?
namespace config;

class Routes
{
  public static $default = [
    'pattern' => '/frontend/index',
    'bundle' => 'CMS',
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'indexAction',
    'route' => 'showArticle'
  ],

  $_ = [
    'exception' => [
      'chunks' => '',
      'core' => true,
      'resources' => [
        'core_css' => ['lionel_exception'],
        'core_js' => ['tools']
      ]
    ],

    'refreshSQLLogs' => [
      'chunks' => ['/dbg/refreshSQLLogs', 'lib/myLibs', 'core', 'profiler', 'refreshSQLLogsAction'],
      'core' => true
    ],

    'clearSQLLogs' => [
      'chunks' => ['/dbg/clearSQLLogs', 'lib/myLibs', 'core', 'profiler', 'clearSQLLogsAction'],
      'core' => true
    ],
    'profiler' => [
      'chunks' => ['/dbg', 'lib/myLibs', 'core', 'profiler', 'indexAction'],
      'core' => true
    ],

    'showArticle' => [
      'chunks' => ['/article/show', 'CMS', 'frontend', 'article', 'showAction'],
      'resources' => [
        'template' => true,
        'bundle_css' => ['header', 'Footer', 'generic', 'main', 'form'],
        'bundle_js' => ['jquery', 'main', 'connection']
      ],
    ],
    'logout' => [
      'chunks' => ['/logout', 'CMS', 'frontend', 'connection', 'logoutAction']
    ],
    'ajaxShowArticle' => [
      'chunks' => ['/ajaxArticle/show', 'CMS', 'frontend', 'ajaxArticle', 'showAction'],
      'bootstrap' => ['article2']
    ],
    'ajaxConnection' => [
      'chunks' => ['/ajaxConnection/ajaxLogin', 'CMS', 'frontend', 'connection', 'ajaxLoginAction'],
      'post' => ['pwd' => ' ', 'email' => ' ']
    ],

    // ---------
    'backendModules' => [
      'chunks' => ['/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'],
      'resources' => [
        '_js' => ['modules'],
        '_css' => ['modules'],
        'bundle_css' => ['generic', 'interface', 'form'],
        'bundle_js' => ['jquery', 'backend']
      ],
      'session' => ['sid' => '1']
    ],

      'backendAjaxModules' => [
        'chunks' => ['/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction'],
        'resources' => [
          '_css' => ['modules'],
          '_js' => ['modules']
        ]
      ],
      'moduleSearch' => [
        'chunks' => ['/backend/ajax/modules/search/module', 'CMS', 'backend', 'ajaxModules', 'searchModuleAction'],
        'get' => ['search' => '']
      ],
      'elementSearch' => [
        'chunks' => ['/backend/ajax/modules/search/element', 'CMS', 'backend', 'ajaxModules', 'searchElementAction'],
        'get' => ['search' => '']
      ],
      'articleSearch' => [
        'chunks' => ['/backend/ajax/modules/search/article', 'CMS', 'backend', 'ajaxModules', 'searchArticleAction'],
        'get' => ['search' => '']
      ],
      'getElements' => [
        'chunks' => ['/backend/ajax/modules/get/elements', 'CMS', 'backend', 'ajaxModules', 'getElementsAction']
      ],

    // -----------

    'backendGeneral' => [
      'chunks' => ['/backend/general', 'CMS', 'backend', 'index', 'generalAction'],
      'resources' => [
        'bundle_css' => ['generic', 'interface', 'form'],
        'bundle_js' => ['jquery', 'backend']
      ]
    ],
    'backendStats' => [
      'chunks' => ['/backend/stats', 'CMS', 'backend', 'index', 'statsAction'],
      'resources' => [
        'bundle_css' => ['generic', 'interface', 'form'],
        'bundle_js' => ['jquery','backend']
      ]
    ],

    // --------------
    'backendUsers' => [
      'chunks' => ['/backend/users', 'CMS', 'backend', 'index', 'usersAction'],
      'resources' => [
        'bundle_css' => ['generic', 'interface', 'form', 'notifications'],
        '_js' => ['_4'=>'users'],
        'bundle_js' => ['jquery', 'backend', 'form', 'notifications']
      ]
    ],
      'addUser' => [
        'chunks' => ['/backend/ajax/users/add', 'CMS', 'backend', 'ajaxUsers', 'addAction'],
        'post' => ['mail' => ' ', 'pwd' => ' ', 'pseudo' => ' ', 'role' => ' '],
        'session' => ['sid' => ['role' => 1]]
      ],
      'editUser' => [
        'chunks' => ['/backend/ajax/users/edit', 'CMS', 'backend', 'ajaxUsers', 'editAction'],
        'post' => ['id_user' => 0, 'mail' => ' ', 'pwd' => ' ', 'pseudo' => ' ', 'role' => ' ', 'oldMail' => ' '],
        'session' => ['sid' => ['role' => 1]]
      ],
      'deleteUser' => [
        'chunks' => ['/backend/ajax/users/delete', 'CMS', 'backend', 'ajaxUsers', 'deleteAction'],
        'post' => ['id_user' => 0],
        'session' => ['sid' => ['role' => 1]]
      ],
      'searchUser' => [
        'chunks' => ['/backend/ajax/users/search', 'CMS', 'backend', 'ajaxUsers', 'searchAction'],
        'post' => ['type' => ' ', 'mail' => ' ', 'pseudo' => ' ', 'role' => ' ', 'limit' => 0, 'prev' => 0, 'last' => 1],
        'session' => ['sid' => ['role' => 1]]
      ],
      'backendAjaxUsers' => [
        'chunks' => ['/backend/ajax/users', 'CMS', 'backend', 'ajaxUsers', 'indexAction'],
        'resources' => [
          '_js' => ['users']
        ]
      ],

      // ----------------

    'backendAjaxGeneral' => [
      'chunks' => ['/backend/ajax/general', 'CMS', 'backend', 'ajaxGeneral', 'indexAction']
    ],
    'backendAjaxStats' => [
      'chunks' => ['/backend/ajax/stats', 'CMS', 'backend', 'ajaxStats', 'indexAction']
    ],

    // keep these routes in last position because it's too generic !!
    'backend' => [
      'chunks' => ['/backend', 'CMS', 'backend', 'index', 'indexAction'],
      'resources' => [
        'template' => true,
        'bundle_css' => ['header', 'footer', 'generic', 'main', 'form'],
        'bundle_js' => ['jquery', 'main', 'connection']
      ]
    ],
    'index' => [
      'chunks' => ['/', 'CMS', 'frontend', 'article', 'showAction'],
      'resources' => [
        'template' => true,
        'bundle_css' => ['header', 'footer', 'generic', 'main', 'form'],
        'bundle_js' => ['jquery', 'main', 'connection']
      ]
    ]
  ];
}
?>
