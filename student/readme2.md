Implementační dokumentace k 2. úloze do IPP 2024/2025  
Jméno a příjmení: **Martin Ševčík**  
Login: **xsevcim00**

## Introduction

This documentation describes the SOL25 interpreter implementation built for IPP Project 2. The interpreter follows an object‑oriented approach with a message‑passing model similar to Smalltalk. The implementation prioritizes clean design principles, particularly Single Responsibility and the Law of Demeter.

## Architecture Overview

The interpreter is designed using several key design patterns:

### Singleton Pattern  
Used for `ClassTable` and `Interpreter` to maintain global state where necessary.

### Proxy Pattern  
`SuperProxy` handles `super` calls while preserving the original receiver object.

### Object‑Message Model  
All operations are performed through message passing between objects.

## Core Components

### Interpreter Structure

### Class Diagram

![Structure Class Diagram](/ippdiagrams/InterpreterClassDiagram.svg)

#### 1. Core Classes  
- **Interpreter**: Entry point that manages program execution  
- **ClassTable**: Manages class definitions and inheritance hierarchy  
- **MessageDispatcher**: Central component for message routing between objects  

#### 2. Object Model  
- **Instance**: Base representation for all SOL25 objects  
- **Base**: Abstract class for built‑in types with common behaviors  
- **Built‑in types**: `IntegerB`, `StringB`, `TrueB`, `FalseB`, `NilB`  
- **Block**: Implementation of code blocks with closures  

#### 3. Class/Method Representation  
- **ClassDef**: Defines a class with methods and parent reference  
- **MethodDef**: Stores method metadata and XML representation  

#### 4. Environment & Scope  
- **Environment**: Stack‑based environment for variable lookup  
- **Frame**: Individual scopes with variables and parameter flags  

#### 5. Error Handling  
- **InterpreterException**: Exception hierarchy with appropriate exit codes  

## Execution Flow

### Execution State Diagram

![Execution Flow](/ippdiagrams/InterpreterExecutionFlowDiagram.svg)

The execution flow follows these main steps:

1. **Initialization**  
   `Interpreter` is instantiated by the framework.  
2. **AST Loading**  
   XML AST is parsed and validated.  
3. **Class Loading**  
   `ClassTable` loads class definitions from the AST.  
4. **Built‑in Setup**  
   Registration of built‑in classes (`Object`, `Integer`, etc.).  
5. **Program Execution**  
   Creates a `Main` instance and invokes its `run` method.  
6. **Message Processing**  
   1. Messages are dispatched via `MessageDispatcher::send`  
   2. Method lookup through inheritance chain  
   3. Built‑in handling for primitive operations  
   4. Dynamic attribute access for public fields  

## Message Dispatch Mechanism

Message handling is central to the interpreter:

1. **Receiver object determines message handling**  
   - Class objects handle constructor methods  
   - `SuperProxy` unwraps to access parent methods  
   - Instance objects look up methods in their class  
   - Built‑in objects implement primitive operations  

2. **Dispatch Priority**  
   1. User‑defined methods  
   2. Built‑in methods  
   3. Public attribute setters (if selector ends with `:`)  
   4. Public attribute getters  
   5. Method not found error (code 51)  

## Error Handling

`InterpreterException` provides factory methods for all error types:

- Parse errors (`22`)  
- Class not found (`31`)  
- Name errors (`32`)  
- Argument errors (`33`)  
- Scope errors (`34`)  
- Method not found (`51`)  
- Runtime errors (`52`, `53`)  
- XML errors (`41`)  

## Diagrams

### State Diagram

![MessageDispatcher Execution Flow](/ippdiagrams/ExpressionEvaluationDiagram.svg)
![MessageDispatcher Execution Flow](/ippdiagrams/ClassDefFlowDiagram.svg)

### Component Flow Diagrams

| Message Dispatcher | Environment Management | Block Execution |
|:------------------:|:----------------------:|:---------------:|
| ![MessageDispatcher Flow](/ippdiagrams/MessageDispatcherFlow3.svg) | ![Environment Flow](/ippdiagrams/EnvironmentFlowDiagram.svg) | ![Block Flow](/ippdiagrams/BlockFlowDiagram.svg) |

## Implementation Details

### Object Model

- All data is represented as objects.  
- Every operation is performed through message passing.  
- Classes form an inheritance hierarchy with `Object` as the root.  
- Public attributes are dynamically accessible through getter/setter messages.

### Message Handling

The `MessageDispatcher` class provides a unified mechanism for message passing:

- **Class objects**: Constructors like `new`, `from:`, and `read`  
- **SuperProxy**: Uses the specified start class for method lookup  
- **Regular instances**:  
  1. Checks user‑defined methods in class hierarchy  
  2. Tries built‑in methods  
  3. Attempts attribute access  
  4. Raises method not found error if all fail  

### Variable Scope

The environment system uses:

- **Environment**: A stack of frames representing nested scopes  
- **Frame**: Individual scope units containing variables  
- Parameters are marked immutable to prevent reassignment  

## Built‑in Types

The interpreter implements several built‑in classes:

- **IntegerB**: Numeric operations and iteration  
- **StringB**: String manipulation and I/O  
- **TrueB** and **FalseB**: Boolean operations  
- **NilB**: Represents `nil` values  
- **Block**: Executable code blocks with closures  

Each has specialized behavior for their class‑specific messages.

## Conclusion

This SOL25 interpreter demonstrates effective object‑oriented design using PHP, with a focus on clean architecture and separation of concerns. The message‑passing model provides flexibility while maintaining consistency throughout the implementation.