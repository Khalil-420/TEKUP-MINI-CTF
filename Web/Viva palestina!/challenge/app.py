from fastapi import FastAPI, HTTPException, Depends, Request

messages = {
    "GET": "Viva palistina !",
    "POST": "Vive la Palestine !",
    "PATCH": "Long live Palestine!",
    "PUT": "Es lebe Palästina!",
    "OPTIONS": "的中文翻译是 巴勒斯坦万岁！",
    "HEAD": "Viva la Palestina!",
    "DELETE": [
            "Securinets{FR33_P4L3ST1N3}",
            "we will never forget, we will never stop sharing, FREE PALESTINE!  "
    ],
}

error = {
    "header": "is_human",
    "message": "Access Denied - YOUR SHOULD ONLY BE HUMAN TO SUPPORT PALESTINE"
}


app = FastAPI()


async def check_header(request: Request):
    if request.headers.get('is_human') != "true":
        raise HTTPException(
            status_code=400, detail=error
        )
    return True


@app.put("/")
async def flag_put(header_check: bool = Depends(check_header)):
    return {":message": messages["PUT"]}


@app.get("/")
async def flag_get(header_check: bool = Depends(check_header)):
    return {"message": messages["GET"]}


@app.post("/")
async def flag_post(header_check: bool = Depends(check_header)):
    return {"message": messages["POST"]}


@app.delete("/")
async def flag_delete(header_check: bool = Depends(check_header)):
    return {"message": messages["DELETE"]}


@app.head("/")
async def flag_head(header_check: bool = Depends(check_header)):
    return {"message": messages["HEAD"]}


@app.options("/")
async def flag_options(header_check: bool = Depends(check_header)):
    return {"message": messages["OPTIONS"]}


@app.patch("/")
async def flag_patch(header_check: bool = Depends(check_header)):
    return {"message": messages["PATCH"]}
