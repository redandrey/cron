# cron
Attempt to build a configurable asynchronous daemon, based on ReactPHP.

# It can:
* read a redis list (`cron:jobs:serverA` by default)
* manage a process pool (just a simulation of a real cron jobs)
* monitor itself (you can send an http request to the `http://{MONITORING_CLIENT_SOCK_ADDR}` to view a current state)

# How to use

* copy `.env.dist` to `.env`
* if you need, change the settings in the `.env` file
* run `php src/cron.php`

### An example of output:
```
16:07:33 |    Monitoring Client | STARTED at 127.3.1.10:8080
16:07:33 |    Monitoring Client | visit checker timer STARTed
16:07:35 |          Job Manager | 3 free slots in the process pool
16:07:35 |         Data Storage | 3 new job(s) requested
16:07:35 |         Data Storage | job queue is empty
16:07:37 |          Job Manager | 3 free slots in the process pool
16:07:37 |         Data Storage | 3 new job(s) requested
16:07:37 |         Data Storage | job queue is empty
16:07:38 |    Monitoring Client | I am unattended since 1562318253.863
16:07:39 |          Job Manager | 3 free slots in the process pool
16:07:39 |         Data Storage | 3 new job(s) requested
16:07:39 |         Data Storage | new job fetched, 2 job(s) left to be fetched
16:07:39 |          Job Manager | New process #1 began to execute the job `job#1562318258-1`
16:07:39 |         Data Storage | 2 new job(s) requested
16:07:39 |         Data Storage | new job fetched, 1 job(s) left to be fetched
16:07:39 |          Job Manager | New process #2 began to execute the job `job#1562318258-2`
16:07:39 |         Data Storage | 1 new job(s) requested
16:07:39 |         Data Storage | new job fetched, 0 job(s) left to be fetched
16:07:39 |          Job Manager | New process #3 began to execute the job `job#1562318258-3`
16:07:41 |          Job Manager | no free slots
16:07:42 |          Job Manager | Process #1 successfully completed the job `job#1562318258-1`
16:07:42 |          Job Manager | 1 free slots in the process pool
16:07:42 |         Data Storage | 1 new job(s) requested
16:07:42 |          Job Manager | Process #2 successfully completed the job `job#1562318258-2`
16:07:42 |          Job Manager | 2 free slots in the process pool
16:07:42 |         Data Storage | 2 new job(s) requested
16:07:42 |         Data Storage | new job fetched, 0 job(s) left to be fetched
16:07:42 |          Job Manager | New process #4 began to execute the job `job#1562318258-4`
16:07:42 |          Job Manager | Process #3 successfully completed the job `job#1562318258-3`
16:07:42 |          Job Manager | 2 free slots in the process pool
16:07:42 |         Data Storage | 2 new job(s) requested
16:07:42 |         Data Storage | job queue is empty
16:07:42 |         Data Storage | job queue is empty
16:07:43 |    Monitoring Client | I am unattended since 1562318253.863
16:07:43 |          Job Manager | 2 free slots in the process pool
16:07:43 |         Data Storage | 2 new job(s) requested
16:07:44 |    Monitoring Client | request from 127.0.0.1
16:07:44 |    Monitoring Client | visit checker timer stopped
16:07:44 |    Monitoring Client | visit checker timer STARTed
16:07:45 |          Job Manager | 2 free slots in the process pool
16:07:45 |         Data Storage | 2 new job(s) requested
16:07:45 |         Data Storage | job queue is empty
16:07:45 |          Job Manager | Process #4 successfully completed the job `job#1562318258-4`
16:07:45 |          Job Manager | 3 free slots in the process pool
16:07:45 |         Data Storage | 3 new job(s) requested
16:07:47 |          Job Manager | 3 free slots in the process pool
16:07:47 |         Data Storage | 3 new job(s) requested
16:07:47 |         Data Storage | job queue is empty
```

# BASH Snippets:

### Add 20 'jobs' to the redis list:
```bash
for i in {1..20}; do redis-cli lpush "cron:jobs:serverA" "job#`date +%s`-${i}"; done
```

### Get current state of the application
```bash
curl http://127.3.1.10:8080/state
```
A result will be similar to this one:
```js
{
    "name": "Cron worker manager for server ca-red",
    "host": "ca-red",
    "started_at": 1562318816.37016,
    "memory": {
        "current": 2697896,
        "peak": 2745352
    },
    "roles": [
        {
            "name": "Monitoring Client",
            "type": "Cron\\Monitoring\\MonitoringClientRole",
            "instance": "Cron\\Monitoring\\MonitoringClient",
            "depends_on": [],
            "state": {
                "visits_count": 5,
                "idle_timeout": 5,
                "is_unattended": false
            }
        },
        {
            "name": "Data Storage",
            "type": "Cron\\Data\\DataStorageRole",
            "instance": "Cron\\Data\\RedisDataStorage",
            "depends_on": [],
            "state": {
                "requests_processed": 5,
                "requests_failed": 0
            }
        },
        {
            "name": "Job Manager",
            "type": "Cron\\JobManager\\JobManagerRole",
            "instance": "Cron\\JobManager\\JobManager",
            "depends_on": [
                "Cron\\Data\\DataStorageRole"
            ],
            "state": {
                "jobs_processed": 4,
                "pool_size_max": 3,
                "pool_size_current": 1,
                "current_processes": [
                    {
                        "id": 4,
                        "job_id": "job#1562318805-4",
                        "started_at": 1562318821.387908,
                        "execution_time": 0.5091660022735596
                    }
                ]
            }
        }
    ]
}
```
