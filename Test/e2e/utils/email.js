import {v4 as uuidv4} from "uuid";

const generateEmail = () => {
    return `playwright-m2-${uuidv4().slice(1, 6)}@mailinator.com`
}

export { generateEmail }