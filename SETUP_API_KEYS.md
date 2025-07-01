# 🔐 API Keys Setup Guide

This guide helps you securely configure API keys for the KMC project.

## 🚨 Security First

**Never commit API keys to the repository!** This project includes security measures to prevent accidental exposure:

- `.cursor/mcp.json` is gitignored (contains sensitive keys)
- `.env` is gitignored (contains sensitive keys)
- Template files (`.cursor/mcp.json.example`, `.env.example`) are safe to commit

## 📋 Quick Setup

### 1. Set up Cursor MCP Configuration

```bash
# Copy the example file
cp .cursor/mcp.json.example .cursor/mcp.json

# Edit the file and add your real API keys
# Replace "ANTHROPIC_API_KEY_HERE" with your actual key
```

### 2. Set up Environment Variables (Optional)

```bash
# Copy the example file
cp .env.example .env

# Edit the file and add your real API keys and database credentials
```

## 🗝️ Required API Keys

You'll need API keys for the AI services you plan to use:

### Required for Task Master AI Tools:

- **Anthropic API Key** - For Claude models
- **OpenAI API Key** - For GPT models
- **Perplexity API Key** - For research capabilities

### Optional Services:

- **Google API Key** - For Gemini models
- **xAI API Key** - For Grok models
- **OpenRouter API Key** - For accessing multiple models
- **Mistral API Key** - For Mistral models
- **Azure OpenAI** - For Azure-hosted OpenAI models
- **Ollama** - For local models

## 🔧 How to Get API Keys

### Anthropic (Claude)

1. Visit [console.anthropic.com](https://console.anthropic.com)
2. Create an account and get your API key
3. Format: `sk-ant-api03-...`

### OpenAI (GPT)

1. Visit [platform.openai.com](https://platform.openai.com)
2. Go to API Keys section
3. Format: `sk-proj-...` or `sk-...`

### Perplexity AI

1. Visit [perplexity.ai](https://www.perplexity.ai)
2. Get API access
3. Format: `pplx-...`

## 🛠️ Configuration Files

### `.cursor/mcp.json`

This file configures the MCP (Model Context Protocol) server for Cursor AI integration:

```json
{
  "mcpServers": {
    "task-master-ai": {
      "command": "npx",
      "args": ["-y", "--package=task-master-ai", "task-master-ai"],
      "env": {
        "ANTHROPIC_API_KEY": "your_actual_key_here",
        "OPENAI_API_KEY": "your_actual_key_here",
        "PERPLEXITY_API_KEY": "your_actual_key_here"
      }
    }
  }
}
```

### `.env` (Optional)

Environment variables for PHP application and CLI tools:

```env
ANTHROPIC_API_KEY=your_actual_key_here
OPENAI_API_KEY=your_actual_key_here
PERPLEXITY_API_KEY=your_actual_key_here
DB_HOST=localhost
DB_NAME=your_database_name
```

## 🧪 Testing Your Setup

### Test MCP Configuration

1. Restart Cursor
2. Try using Task Master tools
3. Check for authentication errors

### Test Environment Variables

```bash
# Test if variables are loaded
echo $ANTHROPIC_API_KEY
```

## 🚫 What NOT to Do

- ❌ Don't commit `.cursor/mcp.json` with real keys
- ❌ Don't commit `.env` with real keys
- ❌ Don't share API keys in chat/email
- ❌ Don't use API keys in filenames
- ❌ Don't store keys in code comments

## ✅ Best Practices

- ✅ Use different keys for development and production
- ✅ Regularly rotate your API keys
- ✅ Monitor API usage and costs
- ✅ Use environment-specific configurations
- ✅ Keep keys in secure password managers

## 🆘 If You Accidentally Commit Keys

1. **Immediately revoke** the exposed keys from the respective services
2. Generate new keys
3. Update your local configuration
4. Consider using `git filter-branch` to remove from history (advanced)

## 📞 Need Help?

- Check the main README.md for project setup
- Review `.cursor/mcp.json.example` for configuration format
- Check `.env.example` for required variables

---

**Remember: Security is everyone's responsibility!** 🔒
