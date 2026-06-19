# agentmemory

Contains configuration for iii-engine and agentmemory

## Prerequisites

- Docker Desktop (for iii-engine)
- Node.js with npm (for agentmemory)
- Windows Terminal

## Getting started

1. Install dependencies:
   ```
   npm install
   ```

2. Start everything in Windows Terminal tabs:
   ```
   .\tools\start-dev.ps1
   ```
   (must be run from the glidingops directory)

## Services

### iii-engine

Runs as a Docker container. Managed by `run-cmd.cmd` (calls `docker run` with config from `iii-config.yaml` and persistent `data/` volume).

Health check: http://localhost:3111/health

### agentmemory

Installed via `npm install` (fetches `@agentmemory/agentmemory` from npm).

Runs at: http://localhost:3113

If it fails to start, run manually:
```
npm run agentmemory
```

### opencode

Requires `opencode` in your PATH. Install via:
```
npm install -g opencode-ai
```

## Configuration

Settings are to be created in `.env` file:
- `OPENROUTER_API_KEY` - API key for LLM access
- `GRAPH_EXTRACTION_ENABLED` - Enable graph extraction
- `CONSOLIDATION_ENABLED` - Enable memory consolidation
- `AGENTMEMORY_AUTO_COMPRESS` - Auto-compress memory
- `AGENTMEMORY_INJECT_CONTEXT` - Inject context into agents