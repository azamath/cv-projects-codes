# WordPress Maintenance Platform

SaaS platform for maintaining websites build on WordPress with such services as backups, security and monitoring.

## Scope of work

Designing and implementing all parts of services: backend, frontend, microservices, worker agent, payments.

## Highlights

- Development of backup micro-service with storing in AWS S3. Written in Lumen.

- Development of security scanner that detects changes in WordPress code, and most common malware patterns. Written in Lumen.

- Development of uptime monitoring micro-service.

- Development of worker agent script that should be installed on client websites. 
  Written using [Symfony](https://symfony.com/doc/2.7/components/index.html) components.

- Payments and subscription management based on Stripe.
