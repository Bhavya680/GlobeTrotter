import json
with open('C:\\Users\\Admin\\.gemini\\antigravity-ide\\brain\\8b9c19d4-26a3-487e-947a-e04234d22899\\.system_generated\\steps\\826\\content.md', 'r') as f:
    content = f.read()
    # The file has "Source: http...\n\n---\n\n{"
    json_str = content[content.find('{'):]
    data = json.loads(json_str)
    print(json.dumps(data['components']['schemas']['RunAgentRequest'], indent=2))
