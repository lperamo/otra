Console colors
==============

In order to convert correctly the ANSI colors code in DOS mode under Windows, you have to retrieve the executable via this url :

http://adoxa.altervista.org/ansicon/

And execute it before launching the console.

Fixtures generation
===================

Common errors :

- Try to use the clean task in simple mode then in full mode if there is no changes.
- For the keys, if we want to refer to an another table id then we have to use the name of the table and not to the name of the property !

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
