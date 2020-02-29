# Stock Data

To import WTD CSV:
```
php artisan stocklist:import wtd storage/data/data-file.csv --exchanges=sgx,hkex,nyse,nasdaq
```

For EOD:
```
php artisan stocklist:import eod storage/data/data-file.txt
```
