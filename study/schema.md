# Voting App — Database Schema

Target: Laravel 13 + PostgreSQL (`voter_db`).

## users

| Column         | Type             | Notes                          |
|----------------|------------------|--------------------------------|
| id             | BIGINT UNSIGNED  | PK                             |
| name           | VARCHAR(255)     |                                |
| matric_number  | VARCHAR(50)      | UNIQUE                         |
| email          | VARCHAR(255)     | UNIQUE                         |
| password       | VARCHAR(255)     |                                |
| role           | ENUM('voter','admin') | DEFAULT 'voter'            |
| is_eligible    | BOOLEAN          | DEFAULT TRUE                   |
| created_at     | TIMESTAMP        |                                |
| updated_at     | TIMESTAMP        |                                |

## elections

| Column      | Type                       | Notes                      |
|-------------|----------------------------|----------------------------|
| id          | BIGINT UNSIGNED            | PK                         |
| title       | VARCHAR(255)               |                            |
| description | TEXT                       |                            |
| status      | ENUM('draft','open','closed') | DEFAULT 'draft'        |
| start_time  | DATETIME                   |                            |
| end_time    | DATETIME                   |                            |
| created_by  | BIGINT UNSIGNED            | FK -> users.id             |
| created_at  | TIMESTAMP                  |                            |
| updated_at  | TIMESTAMP                  |                            |

## positions

| Column      | Type            | Notes                        |
|-------------|-----------------|------------------------------|
| id          | BIGINT UNSIGNED | PK                           |
| election_id | BIGINT UNSIGNED | FK -> elections.id           |
| title       | VARCHAR(255)    | e.g. "Class Representative"  |
| description | TEXT            |                              |
| created_at  | TIMESTAMP       |                              |
| updated_at  | TIMESTAMP       |                              |

## candidates

| Column      | Type            | Notes                                             |
|-------------|-----------------|---------------------------------------------------|
| id          | BIGINT UNSIGNED | PK                                                |
| position_id | BIGINT UNSIGNED | FK -> positions.id                                |
| user_id     | BIGINT UNSIGNED | FK -> users.id, NULLABLE (candidate may be non-student) |
| name        | VARCHAR(255)    |                                                   |
| photo_path  | VARCHAR(255)    | NULLABLE                                          |
| manifesto   | TEXT            | NULLABLE                                          |
| created_at  | TIMESTAMP       |                                                   |
| updated_at  | TIMESTAMP       |                                                   |

## votes

| Column       | Type            | Notes              |
|--------------|-----------------|--------------------|
| id           | BIGINT UNSIGNED | PK                 |
| election_id  | BIGINT UNSIGNED | FK -> elections.id |
| position_id  | BIGINT UNSIGNED | FK -> positions.id |
| candidate_id | BIGINT UNSIGNED | FK -> candidates.id|
| voter_id     | BIGINT UNSIGNED | FK -> users.id     |
| created_at   | TIMESTAMP       |                    |

UNIQUE CONSTRAINT `(position_id, voter_id)` — prevents double voting per position.
This is the critical integrity guarantee of the whole schema.

## audit_logs (optional but recommended for the report/demo)

| Column     | Type            | Notes              |
|------------|-----------------|--------------------|
| id         | BIGINT UNSIGNED | PK                 |
| user_id    | BIGINT UNSIGNED | FK -> users.id     |
| action     | VARCHAR(255)    | e.g. "opened election", "cast vote" |
| created_at | TIMESTAMP       |                    |
