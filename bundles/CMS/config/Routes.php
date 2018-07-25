<?
/** CMS ROUTES */
return [
  'showArticle' => [
    'chunks' => ['/article/show', 'CMS', 'frontend', 'article', 'showAction'],
    'resources' => [
      'template' => true,
      'bundle_css' => ['showArticle'],
      'bundle_js' => ['base', 'main', 'connection']
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
  'ajaxMailingList' => [
    'chunks' => ['/ajaxMailingList', 'CMS', 'frontend', 'ajaxMailingList', 'addAction']
  ],

  // ---------
  'backendModules' => [
    'chunks' => ['/backend/modules', 'CMS', 'backend', 'index', 'modulesAction'],
    'resources' => [
      '_js' => ['modules'],
      '_css' => ['modules'],
      'bundle_css' => ['backendModules'],
      'bundle_js' => ['base', 'backend']
    ],
    'session' => ['sid' => '1']
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

  'backendAjaxModules' => [
    'chunks' => ['/backend/ajax/modules', 'CMS', 'backend', 'ajaxModules', 'indexAction'],
    'resources' => [
      '_css' => ['modules'],
      '_js' => ['modules']
    ]
  ],

  // -----------

  'backendGeneral' => [
    'chunks' => ['/backend/general', 'CMS', 'backend', 'index', 'generalAction'],
    'resources' => [
      '_css' => ['general'],
      'bundle_css' => ['backendGeneral'],
      '_js' => ['general'],
      'bundle_js' => ['base', 'backend']
    ]
  ],
  'backendAjaxGeneral' => [
    'chunks' => ['/backend/ajax/general', 'CMS', 'backend', 'ajaxGeneral', 'indexAction'],
    'resources' => [
      '_css' => ['general'],
      '_js' => ['general']
    ]
  ],
  'backendStats' => [
    'chunks' => ['/backend/stats', 'CMS', 'backend', 'index', 'statsAction'],
    'resources' => [
      'bundle_css' => ['backendStats'],
      'bundle_js' => ['base', 'backend']
    ]
  ],

  // --------------
  'backendUsers' => [
    'chunks' => ['/backend/users', 'CMS', 'backend', 'index', 'usersAction'],
    'resources' => [
      '_css' => ['users'],
      'bundle_css' => ['backendUsers'],
      'core_css' => ['lightbox'],
      '_js' => ['_5'=>'users'],
      'bundle_js' => ['base', 'backend', 'form', 'notifications'],
      'core_js' => ['_4' => 'lightbox']
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
      '_css' => ['users'],
      'core_css' => ['lightbox'],
      '_js' => ['users'],
      'core_js' => ['_1' => 'lightbox']
    ]
  ],

  // ----------------

  'backendAjaxStats' => [
    'chunks' => ['/backend/ajax/stats', 'CMS', 'backend', 'ajaxStats', 'indexAction']
  ],

  // keep these routes in last position because it's too generic !!
  'backend' => [
    'chunks' => ['/backend', 'CMS', 'backend', 'index', 'indexAction'],
    'resources' => [
      'template' => true,
      'bundle_css' => ['showArticle'],
      'bundle_js' => ['base', 'main', 'connection']
    ]
  ],
  'index' => [
    'chunks' => ['/', 'CMS', 'frontend', 'article', 'showAction'],
    'resources' => [
      'template' => true,
      'bundle_css' => ['showArticle'],
      'bundle_js' => ['base', 'main', 'connection']
    ]
  ]
];
?>
