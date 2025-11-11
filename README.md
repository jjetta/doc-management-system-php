## Loan Document Management System 

A PHP/MySQL backend system designed to automate the processing and management of loan-related documents. This project interacts with an external API to fetch, queue, and manage loan documents while keeping track of document types, loans, and API sessions.

---

## Project Overview

The Loan Document Management System automates the workflow of retrieving, organizing, and storing loan-related documents. The system is structured around several key components:

* **API Session Management**

  * Handles creation, tracking, and closing of API sessions with timestamps.

* **Document Processing**

  * Queries available documents from an external API.
  * Validates filenames following the convention: `loan_number-doctype-timestamp.pdf`.
  * Queues documents in the database for download or further processing.

* **Database Safety and Integrity**

  * Prepared statements for secure database operations. 
  * All statements and result sets are properly closed to prevent resource leaks.

* **Logging**

  * Centralized logging for all cron jobs.
  * Logs every API interaction, database operation, and document processing step.
  * Provides detailed success and error messages for easier debugging and monitoring.

* **Cron Job Automation**

  * Scripts are scheduled to run automatically for creating sessions, querying files, downloading pending files, and closing sessions.
  * A single consolidated log file simplifies monitoring and allows easy log rotation.

* **Misc**

  * Within the scope of this class, ORMs are not permitted, so raw SQL queries must be used.
---

## Database Design

The system uses six primary tables:

1. **`api_sessions`** – Tracks API sessions (`session_id`, `created_at`).
2. **`loans`** – Stores loan records, each identified by a `loan_number`.
3. **`documents`** – Tracks documents associated with loans (`loan_id`, `file_name`, `doctype_id`)
4. **`document_types`** – Stores unique document types.
5. **`document_contents`** - Stores the actual BLOB contents of the pdfs.
6. **`document_statuses`** - Keeps track of the status of individual documents (whether a document is pending download, downloaded, or failed to download);

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
* And a lot of debugging haha.

---

## Summary

This project demonstrates a structured approach to automating document management workflows. By combining database normalization, logging, and cron job automation, the system ensures loan documents are consistently tracked, organized, and processed with minimal manual intervention.

This project served as a practical exercise in backend design, error handling, and system reliability.
