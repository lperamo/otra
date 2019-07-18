[Home](/README.md) / [Installation](../projectConfiguration.md) / [Server configuration](../serverConfiguration.md) / DNS

### Configuring DNS

As usual you'll find your hosts in `/etc/hosts` on *nix systems and under `C:\WINDOWS\system32\drivers\etc\hosts`.
Add those informations in it :

    127.0.0.1 yourdomain.com
    ::1       yourdomain.com
    
NB : `::1` is for IPv6.
