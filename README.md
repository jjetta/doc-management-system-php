## Loan Document Management System 

A PHP/MySQL backend system designed to automate the processing and management of loan-related documents. This project interacts with an external API to fetch, queue, and manage loan documents while keeping track of document types, loans, and API sessions.

---

## Project Overview

The system automates the workflow of retrieving, organizing, and storing loan-related documents. It's structured around several key components:

* **API Session Management**

  * Handles creation and tracking of API sessions. (with timestamps!)

* **Document Processing**

  * Available documents are queried from an external API and stored in a database.
  * Filenames are validated according to this convention: `loan_number-doctype-timestamp.pdf`.
  * Documents in the database are queued for download or further processing.

* **Cron Job Automation**

  * Scripts are scheduled to automatically run hourly for creating sessions, querying files, and downloading pending files.

* **Logging**

  * All cron jobs write to a centralized log file.
  * Every API interaction, database operation, and document processing step is logged.
  * Detailed success and error messages are provided for easier debugging and monitoring.
  * Logs are automatically archived, compressed and rotated daily. 


* **Note**

  *  Raw SQL queries are used throughout the project since ORMs are not permitted within the scope of this class.
---

## High Level Database Design

The system uses six primary tables:

1. **`api_sessions`** – Tracks API sessions. (`session_id`, `created_at`)
2. **`loans`** – Stores loan numbers. (`loan_id` and `loan_number`)
3. **`documents`** – Tracks documents associated with loans. (`document_id`, `loan_id`, `doctype_id`, `uploaded_at`, `file_name`)
4. **`document_types`** – Stores unique document types. (`doctype_id`, `doctype`)
5. **`document_contents`** - Stores the actual BLOB content of the pdfs. (`document_id`, `content`, `size`)
6. **`document_statuses`** - Keeps track of the status of individual documents (whether a document is pending download, downloaded, or failed to download); (`document_id`, `status`)

---

## Filename Convention

Documents retrieved from the API follow this pattern:

```
loan_number-doctype-timestamp.pdf
```

* `loan_number`: Identifier for the loan.
* `doctype`: Type of document, may include numeric suffixes (e.g., `_1`).
* `timestamp`: Time the file was generated.
* `.pdf` extension is mandatory.

The system validates and normalizes these filenames before storing or processing them.

---

## What I Learned

I've learned so much thus far working on this project. I gained valuable experience in:

* Backend automation with PHP and MySQL
* **Linux**: crontabs, file permissions
* **Database safety practices**: prepared statements, resource management, and error handling.
* **Working with external APIs** and structuring cron-based workflows.
* **File parsing and normalization** using PHP string and regex functions.
* **Logging**: centralized logs, success/failure tracking, and exception-safe logging.
* **System architecture** for long-running automated systems that need data integrity, reliability, and traceability.
* Lots of debugging haha.

---

## Summary

This project demonstrates a structured approach to automating document management workflows. By combining database normalization, logging, and cron job automation, the system ensures loan documents are consistently tracked, organized, and processed with minimal manual intervention.

This project served as a practical exercise in backend design, error handling, and system reliability.
