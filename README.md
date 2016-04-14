# nl.pum.cvmutation

Send an E-mail to Sector Coordinator and set CV in Mutation whenever an Expert change his own CV.

This extension contains two helper API's.
**cvmutation.pre** Which stores the original cv data
**cvmutation.post** Which stores the new cv data

This extension also contains a cron job which processes all cvmutation which haven't 
changed in the last three hours. It will create an activity CV Mutation for all those 
mutations.

## How to set up e-mail

To e-mail a sector coordinator you should set up scheduled reminders
This extension only creates an activity of type 'CV Mutation' assigned to the sector coordinator.


