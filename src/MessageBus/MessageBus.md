# Message Bus

3 types of message bus:

## Command Bus 

Command bus finds 1 handler and executes it. It is used to execute commands.
Command : handler 1:1

## Query Bus

When dispatching a query on the bus, we expect a given object result. Based on Query + expected Result type we find the handler and execute it. 

// todo, later
## Event Bus
1 event can have multiple handlers. Event : handler 1:n
