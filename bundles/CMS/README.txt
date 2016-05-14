Instructions for the schema.yml CMS file
========================================

# Le type de module est comme suit:
# 0: connexion
# 1: menu vtcl
# 2: menu hztl
# 3: module article
# 4: mailing list
# 5: autre module

# droit
# 0: Admin
# 1: Enregistré
# 2: Public

# dans articles
# sum=>  la note; count => le nb de votes

# parent => si c'est à -1 il n'y en a pas, sinon c'est le module concerné par l'id en question

# rôle (voir la fixture pour connaître le principe du masque en détail)
# 1: admin (peut créer des modules, menus, écrire, modifier, consulter... enfin tout quoi)
# 2: rédacteur (peut écrire, modifier, consulter des articles)
# 3: modérateur (peut modifier et consulter)
# 4: utilisateur (peut juste consulter)
