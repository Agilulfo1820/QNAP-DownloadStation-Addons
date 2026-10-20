# TorrentGalaxy per QNAP Download Station

Provider PHP realizzato sullo schema ISite/ISearch degli addon esistenti.

## Installazione

Copia questa cartella `torrentgalaxy.one` nella directory `addons` di Download Station sul NAS, come gli altri provider del repository. Individua il percorso effettivo sul tuo NAS: non presumere che sia uguale tra modelli. Poi aggiorna l'elenco in Impostazioni > Add-ons e abilita il provider.

Per aggiungere i sorgenti al repository Windows, copia questa cartella in:
`C:\Users\brone\personal-projects\QNAP-DownloadStation-Addons\torrentgalaxy.one`

Lo ZIP e' un archivio dei sorgenti, non un pacchetto QNAP `.addon` firmato.

## Funzionamento

- Ricerca GET `/get-posts/?keywords=...&page=N`, con codifica delle parole chiave.
- Estrae i risultati dalle righe `tgxtablerow`, non dalle pubblicita' o dai suggerimenti.
- Visita ogni `/post-detail/.../` e legge il magnet da un link HTML.
- Mantiene la pagina di dettaglio in `link` e il magnet completo, tracker inclusi, in `enclosure_url`.
- Interpreta GB come decimale e GiB come binario; la lista fornita usa GB.
- Data approssimata dall'eta' relativa della lista; se non riconoscibile resta null.
- Salta dettagli non raggiungibili o privi di magnet valido, elimina duplicati e rispetta il limite richiesto.
- Massimo 20 pagine per ricerca; arresto anche su pagina vuota o ripetuta.
- Come negli altri addon, il parametro categoria non viene applicato come filtro.

## Verifica e limiti

Le espressioni di parsing sono state controllate sui due HTML allegati: 50 risultati unici, titolo, categoria, dimensione, seed/peer e magnet con tracker. Verificato anche il JSON del manifest.

PHP e Download Station non sono stati eseguiti in questa sessione: serve una prova sul NAS. Non e' stata verificata la raggiungibilita' live del sito. Richieste seriali ai dettagli possono rallentare ricerche con molti risultati; eventuali CAPTCHA o protezioni JavaScript non sono gestiti. Il codice non avvia download durante la ricerca: restituisce i magnet a Download Station.
